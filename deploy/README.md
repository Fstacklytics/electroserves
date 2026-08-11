# Deploying ElectroServes

Operational runbook for the ElectroServes marketing site. Everything here is
specific to this repository: the paths, command names, environment variables and
config keys below are the ones the application actually reads. Where a value is
yours to choose it is marked **CUSTOMISE**.

The site is a public, file-backed Laravel application. **There is no database.**
Content lives in `content/` as Markdown and YAML and is edited through Decap CMS,
which commits back to GitHub. That single fact shapes the whole deployment: a
"content update" is a Git change, backups are Git plus a handful of files, and
there are no migrations to run.

- [1. Requirements](#1-requirements)
- [2. Server preparation](#2-server-preparation)
- [3. Directory layout](#3-directory-layout)
- [4. Environment configuration](#4-environment-configuration)
- [5. First deployment](#5-first-deployment)
- [6. Routine deployment](#6-routine-deployment)
- [7. Nginx and TLS](#7-nginx-and-tls)
- [8. Caching and invalidation](#8-caching-and-invalidation)
- [9. Decap CMS and GitHub OAuth](#9-decap-cms-and-github-oauth)
- [10. Backup and restore](#10-backup-and-restore)
- [11. Health and smoke checks](#11-health-and-smoke-checks)
- [12. Rollback](#12-rollback)
- [13. Failure recovery](#13-failure-recovery)
- [14. Monitoring, logs and incident response](#14-monitoring-logs-and-incident-response)
- [15. Security and dependency audits](#15-security-and-dependency-audits)
- [16. Production readiness checklist](#16-production-readiness-checklist)

---

## 1. Requirements

| Component | Version | Why |
|---|---|---|
| PHP | **8.3 or newer** | `composer.json` requires `^8.3`; the codebase uses typed constants and readonly promotion. |
| Composer | 2.5+ | Lock-file format and `--no-dev` audit behaviour. |
| Node.js | **20 LTS or newer** | Vite 7 / Tailwind 3 toolchain in `package.json`. |
| npm | 10+ | Ships with Node 20. |
| Nginx | 1.18+ | `http2 on;` syntax used in `deploy/nginx.conf` (1.25+ preferred; see §7). |
| PHP-FPM | matching PHP | Socket path in `deploy/nginx.conf` is `php8.3-fpm.sock`. |
| Git | 2.30+ | Release checkout and CMS content commits. |

### PHP extensions

Laravel 11 baseline plus what this app uses. Verify with `php -m`:

```
ctype  curl  dom  fileinfo  filter  hash  iconv  json  libxml  mbstring
openssl  pcre  session  tokenizer  xml  xmlwriter  zip
```

`mbstring` and `dom` are non-optional here: Markdown parsing and the sitemap
builder both depend on them. **No database extension is required** — do not
install or configure `pdo_mysql` for this project.

```bash
sudo apt install -y php8.3-fpm php8.3-cli php8.3-mbstring php8.3-xml \
                    php8.3-curl php8.3-zip php8.3-intl
```

---

## 2. Server preparation

Assumes Ubuntu 22.04/24.04 LTS. **CUSTOMISE** for your distribution.

```bash
# 1. Base packages
sudo apt update && sudo apt upgrade -y
sudo apt install -y nginx git unzip curl

# 2. PHP (see §1)
sudo apt install -y php8.3-fpm php8.3-cli php8.3-mbstring php8.3-xml \
                    php8.3-curl php8.3-zip php8.3-intl

# 3. Node 20
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install -y nodejs

# 4. Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# 5. Deploy user (do not deploy as root)
sudo adduser --system --group --shell /bin/bash deploy
sudo usermod -aG www-data deploy
```

### PHP-FPM pool

Edit `/etc/php/8.3/fpm/pool.d/www.conf`:

```ini
user = www-data
group = www-data
listen = /run/php/php8.3-fpm.sock       ; must match fastcgi_pass in deploy/nginx.conf
listen.owner = www-data
listen.group = www-data

pm = dynamic
pm.max_children = 20                    ; CUSTOMISE: ~ (RAM available / 60MB)
pm.start_servers = 4
pm.min_spare_servers = 2
pm.max_spare_servers = 6
pm.max_requests = 500
```

Harden `/etc/php/8.3/fpm/php.ini`:

```ini
expose_php = Off
display_errors = Off                    ; errors go to the log, never to a visitor
log_errors = On
memory_limit = 256M
post_max_size = 2M                      ; keep at or below client_max_body_size
upload_max_filesize = 2M
max_execution_time = 30                 ; keep at or below fastcgi_read_timeout

opcache.enable = 1
opcache.memory_consumption = 128
opcache.max_accelerated_files = 20000
opcache.validate_timestamps = 0         ; requires an FPM reload on every deploy (§6)
```

```bash
sudo systemctl restart php8.3-fpm
```

> `opcache.validate_timestamps = 0` is the fastest setting but makes PHP-FPM
> blind to file changes. The deploy script in §6 reloads FPM for exactly this
> reason. If you skip the reload, the site keeps serving the previous release.

### Firewall

```bash
sudo ufw allow OpenSSH
sudo ufw allow 'Nginx Full'
sudo ufw enable
```

---

## 3. Directory layout

Releases are deployed to timestamped directories and activated by flipping a
symlink, so a rollback is one atomic operation (§12).

```
/var/www/electroserves/
├── current -> releases/20260811143000     # symlink; Nginx root is current/public
├── releases/
│   ├── 20260811143000/
│   └── 20260810091500/                    # keep the last 5
├── shared/
│   ├── .env                               # never in Git
│   └── storage/                           # logs, caches, sessions — survives deploys
└── repo/                                  # bare-ish working clone used to build
```

`content/` is **not** shared. It is part of the release, because content is
versioned in Git and pulled with the code. This is what makes the CMS workflow
and rollback consistent: rolling back the code rolls back the content with it.

```bash
sudo mkdir -p /var/www/electroserves/{releases,shared/storage}
sudo mkdir -p /var/www/certbot
sudo chown -R deploy:www-data /var/www/electroserves
```

---

## 4. Environment configuration

`.env` lives at `/var/www/electroserves/shared/.env` and is symlinked into each
release. Start from `.env.example`, which documents every variable.

**The application refuses to boot if a required variable is missing.**
`App\Providers\EnvironmentValidationServiceProvider` checks the list in
`config/electroserves.php` at `required_env` — this is deliberate, so a
misconfiguration fails at deploy time instead of when a visitor submits the
contact form.

### Required

| Variable | Production value | Notes |
|---|---|---|
| `APP_NAME` | `ElectroServes` | Used in titles and mail. |
| `APP_ENV` | `production` | Also disables the `/styleguide` route. |
| `APP_KEY` | *(generated)* | See below. Never commit or reuse across environments. |
| `APP_DEBUG` | `false` | **Non-negotiable.** `true` leaks env vars on any error page. |
| `APP_URL` | `https://electroserves.co.tz` | **CUSTOMISE.** Canonical URLs, sitemap and mail links are built from this. Must be `https://`, no trailing slash. |
| `MAIL_MAILER` | `smtp` | |
| `MAIL_FROM_ADDRESS` | `noreply@electroserves.co.tz` | **CUSTOMISE.** Must be a domain you can authenticate. |
| `MAIL_FROM_NAME` | `"${APP_NAME}"` | |
| `CONTACT_NOTIFICATION_EMAIL` | `hello@electroserves.co.tz` | **CUSTOMISE.** Where contact submissions are delivered. |

### Required when `MAIL_MAILER=smtp`

Listed in `config/electroserves.php` at `required_env_smtp`:

| Variable | Notes |
|---|---|
| `MAIL_HOST` | **CUSTOMISE** — your SMTP relay. |
| `MAIL_PORT` | `587` for STARTTLS, `465` for implicit TLS. |
| `MAIL_USERNAME` / `MAIL_PASSWORD` | Set in `.env` only. Never in this file, in Git, or in a ticket. |
| `MAIL_ENCRYPTION` | `tls` |

### Optional

| Variable | Default | Notes |
|---|---|---|
| `APP_TIMEZONE` | `Africa/Dar_es_Salaam` | Post dates render in this zone. |
| `APP_LOCALE` | `en` | Only `en` is translated today. |
| `CONTENT_PATH` | `<project>/content` | Leave unset unless content is stored outside the release. |
| `CONTENT_CACHE_ENABLED` | `true` | Keep `true` in production. |
| `CONTENT_CACHE_TTL` | `300` | Seconds. |
| `RESPONSE_CACHE_ENABLED` | `false` | **Set to `true` in production.** See §8. |
| `RESPONSE_CACHE_STORE` | app default | Name a dedicated store to clear pages without clearing everything (§8). |
| `RESPONSE_CACHE_TTL` | `600` | Fallback for routes with no specific TTL. |
| `RESPONSE_CACHE_TTL_HOME` / `_SERVICES` / `_PROJECTS` / `_BLOG` / `_STATIC` / `_LEGAL` / `_SITEMAP` | see `config/electroserves.php` | Per-route overrides. |
| `CONTACT_RATE_LIMIT_MAX` | `5` | Per IP. If you raise this, revisit the Nginx zone (§7). |
| `CONTACT_RATE_LIMIT_DECAY` | `3600` | Seconds. |
| `LOG_CHANNEL` | `stack` | |
| `LOG_LEVEL` | `warning` in production | `debug` will fill the disk. |
| `CACHE_STORE` | `file` | `redis` is supported if you have one; not required. |
| `SESSION_DRIVER` | `file` | Sessions only hold CSRF tokens and flash messages. |
| `QUEUE_CONNECTION` | `sync` | The site queues nothing; contact mail is sent inline. |

### Generating `APP_KEY`

```bash
cd /var/www/electroserves/current
php artisan key:generate --force
```

Run this **once**, on the server, and back the value up (§10). Changing it
invalidates every existing session and every encrypted cookie.

---

## 5. First deployment

```bash
sudo -u deploy -i
cd /var/www/electroserves

# 1. Clone
git clone https://github.com/Fstacklytics/electroserves.git repo
cd repo && git checkout main

# 2. Create the shared environment file
cp .env.example ../shared/.env
nano ../shared/.env          # fill in §4, set APP_ENV=production, APP_DEBUG=false
chmod 600 ../shared/.env

# 3. Cut the first release using the script in §6
```

Then, once: generate the key (§4), configure Nginx and TLS (§7), and run the
smoke checks (§11).

---

## 6. Routine deployment

Save as `/var/www/electroserves/deploy.sh`, `chmod +x`, run as `deploy`.

```bash
#!/usr/bin/env bash
set -euo pipefail

APP_DIR=/var/www/electroserves
RELEASE="$APP_DIR/releases/$(date +%Y%m%d%H%M%S)"
BRANCH="${1:-main}"
KEEP=5

echo "==> Fetching $BRANCH"
cd "$APP_DIR/repo"
git fetch --all --prune
git checkout "$BRANCH"
git pull --ff-only origin "$BRANCH"

echo "==> Creating release $RELEASE"
mkdir -p "$RELEASE"
git archive "$BRANCH" | tar -x -C "$RELEASE"

echo "==> Linking shared state"
ln -sfn "$APP_DIR/shared/.env" "$RELEASE/.env"
rm -rf "$RELEASE/storage"
ln -sfn "$APP_DIR/shared/storage" "$RELEASE/storage"

echo "==> Installing PHP dependencies"
cd "$RELEASE"
composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist

echo "==> Building assets"
npm ci
npm run build
rm -rf node_modules           # not needed at runtime; keeps the release small

echo "==> Warming framework caches"
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "==> Activating"
ln -sfn "$RELEASE" "$APP_DIR/current"

echo "==> Reloading PHP-FPM (required: opcache.validate_timestamps=0)"
sudo systemctl reload php8.3-fpm

echo "==> Clearing caches built from the previous release"
cd "$APP_DIR/current"
php artisan content:flush

echo "==> Pruning old releases"
cd "$APP_DIR/releases"
ls -1dt */ | tail -n +$((KEEP + 1)) | xargs -r rm -rf

echo "==> Deployed $RELEASE"
```

Grant only the one reload permission the script needs, in
`/etc/sudoers.d/electroserves-deploy`:

```
deploy ALL=(root) NOPASSWD: /bin/systemctl reload php8.3-fpm, /bin/systemctl reload nginx
```

### Ownership and permissions

Application files are owned by `deploy` and readable by `www-data`; only the
storage tree is writable by the web server.

```bash
sudo chown -R deploy:www-data /var/www/electroserves/current/
sudo find /var/www/electroserves/current/ -type d -exec chmod 755 {} \;
sudo find /var/www/electroserves/current/ -type f -exec chmod 644 {} \;

sudo chown -R www-data:www-data /var/www/electroserves/shared/storage
sudo chmod -R 775 /var/www/electroserves/shared/storage
sudo chmod -R 775 /var/www/electroserves/current/bootstrap/cache

sudo chmod 600 /var/www/electroserves/shared/.env
sudo chown deploy:www-data /var/www/electroserves/shared/.env
```

> `content/` must stay **read-only** to `www-data`. The application never writes
> it; Decap CMS changes arrive through GitHub and land via a deployment. A
> web-writable content directory would turn a CMS token compromise into remote
> file write.

### Cache commands used above

| Command | Effect |
|---|---|
| `php artisan config:cache` | Compiles `config/` into one file. **Re-run after any `.env` change** — with a config cache in place, `.env` is no longer read at runtime. |
| `php artisan route:cache` | Compiles the route table. |
| `php artisan view:cache` | Pre-compiles Blade templates. |
| `php artisan optimize` | Runs the three above together. |
| `php artisan optimize:clear` | Drops all of them plus the application cache. |
| `php artisan content:flush` | Clears the parsed Markdown/YAML cache **and** the rendered page cache. |
| `php artisan responsecache:clear` | Clears only the rendered page cache. |

---

## 7. Nginx and TLS

```bash
sudo cp /var/www/electroserves/current/deploy/nginx.conf \
        /etc/nginx/sites-available/electroserves
sudo nano /etc/nginx/sites-available/electroserves     # every CUSTOMISE marker
sudo ln -s /etc/nginx/sites-available/electroserves /etc/nginx/sites-enabled/
sudo rm -f /etc/nginx/sites-enabled/default

sudo nginx -t          # MUST pass before reloading
sudo systemctl reload nginx
```

**Rate limit zones.** `deploy/nginx.conf` documents two `limit_req_zone`
directives but leaves them commented, because `limit_req_zone` is only valid at
`http{}` scope and this file is included from `sites-enabled`. To enable them,
put the two lines in `/etc/nginx/conf.d/electroserves-limits.conf` and uncomment
the matching `limit_req` lines in the server block.

The Nginx contact limit is intentionally **looser** than the application's
5-per-hour limiter in `App\Providers\RateLimitServiceProvider`. Laravel should be
the layer that turns away an over-eager visitor, because it returns a translated,
friendly message; Nginx exists only to absorb floods. `DeploymentConfigTest`
asserts this ordering. If you change `CONTACT_RATE_LIMIT_MAX`, re-check it.

**Nginx older than 1.25** does not support `http2 on;`. Replace:

```nginx
listen 443 ssl;
http2 on;
```

with the pre-1.25 form:

```nginx
listen 443 ssl http2;
```

### Certificates

```bash
sudo apt install -y certbot python3-certbot-nginx

sudo certbot certonly --webroot -w /var/www/certbot \
  -d electroserves.co.tz -d www.electroserves.co.tz \
  --email admin@electroserves.co.tz --agree-tos --no-eff-email

sudo systemctl reload nginx
```

Renewal is installed automatically as a systemd timer. Verify it, and make sure
Nginx actually picks up a renewed certificate:

```bash
systemctl list-timers | grep certbot
sudo certbot renew --dry-run
echo 'sudo systemctl reload nginx' | sudo tee /etc/letsencrypt/renewal-hooks/deploy/reload-nginx.sh
sudo chmod +x /etc/letsencrypt/renewal-hooks/deploy/reload-nginx.sh
```

> A renewal that is not followed by a reload is the most common cause of an
> "expired certificate" on a server where renewal is working perfectly.

### Compression

gzip is on by default in `deploy/nginx.conf`. Brotli is documented but commented
out because stock Nginx has no Brotli module:

```bash
sudo apt install -y libnginx-mod-http-brotli-filter libnginx-mod-http-brotli-static
# then uncomment the brotli block in the server config
sudo nginx -t && sudo systemctl reload nginx
```

Keep gzip enabled alongside it — Nginx picks per request based on
`Accept-Encoding`.

### Security headers

The config deliberately does **not** set CSP, HSTS, `Referrer-Policy` or
`Permissions-Policy`. `App\Http\Middleware\SecurityHeadersMiddleware` sets them on
every response in every environment, so they are covered by the test suite.
Adding one of them inside an Nginx `location` would cause Nginx to drop all
inherited `add_header` directives in that block — a header loss that only shows
up in production. `DeploymentConfigTest` fails if these headers are added here.

---

## 8. Caching and invalidation

Three independent layers:

| Layer | What it holds | Cleared by |
|---|---|---|
| Framework caches | config, routes, compiled views | `php artisan optimize:clear` |
| Content cache | parsed Markdown/YAML from `content/` | `php artisan content:flush` |
| Response cache | fully rendered HTML pages | `php artisan responsecache:clear` |

Enable the response cache in production:

```dotenv
RESPONSE_CACHE_ENABLED=true
```

### What is and is not cached

Per-route TTLs are in `config/electroserves.php` under `response_cache.routes`
(home 10 min, services/projects 30 min, blog 10 min, about/FAQ/testimonials 1 h,
legal 24 h, sitemap 1 h).

The cache **never** stores:

- anything but `GET` and `HEAD`;
- redirects, error responses, and validation failures;
- responses carrying flash data or a session `errors` bag;
- the routes in `response_cache.excluded_routes` — `contact`, `contact.store`,
  `styleguide`. The contact page renders a CSRF token and stores a timestamp in
  the session for the spam check, so a shared copy would both leak a token and
  defeat the check.

Query strings are part of the cache key, so `?page=2` and `?category=safety`
are cached separately. Tracking parameters (`utm_*`, `gclid`, `fbclid`, `ref`)
are stripped from the key so a campaign link does not fragment the cache.

Diagnose with the `X-Response-Cache` header — `HIT`, `MISS` or `BYPASS`:

```bash
curl -sI https://electroserves.co.tz/ | grep -i x-response-cache
```

### Invalidation

Entries embed a fingerprint of `content/` (file paths plus mtimes), so a content
change invalidates the affected pages automatically on the next request. Explicit
clearing is still needed after a deployment or a manual file edit:

```bash
cd /var/www/electroserves/current
php artisan content:flush          # both content and page caches
```

Give the page cache its own store so clearing it cannot disturb anything else:

```dotenv
RESPONSE_CACHE_STORE=responses
```

```php
// config/cache.php
'responses' => [
    'driver' => 'file',
    'path' => storage_path('framework/cache/responses'),
],
```

### Warming

Optional; the first visitor to each page warms it anyway. To pre-warm the main
surfaces after a deploy, append to `deploy.sh`:

```bash
for path in / /services /projects /blog /about /testimonials /faq \
            /privacy-policy /terms /sitemap.xml; do
  curl -sS -o /dev/null -w "%{http_code} %{url_effective}\n" \
       "https://electroserves.co.tz${path}"
done
```

Do not warm `/contact` — it is excluded, and requesting it only creates a session.

---

## 9. Decap CMS and GitHub OAuth

The CMS is a static page at `/admin`, configured in `public/admin/config.yml`
(backend `github`, repo `Fstacklytics/electroserves`, branch `main`,
`publish_mode: editorial_workflow`, so edits arrive as pull requests).

Decap's GitHub backend needs an OAuth **client**, which needs a small server-side
component to exchange the code for a token. This application deliberately does
not implement one — it would mean handling credentials in a site that otherwise
has no authentication at all.

Choose one:

1. **Netlify / Cloudflare Pages as the OAuth broker only** (simplest). Deploy an
   OAuth provider function, then point the CMS at it with `base_url` and
   `auth_endpoint` in `public/admin/config.yml`.
2. **Self-hosted OAuth proxy** on a subdomain, e.g. one of the community
   `netlify-cms-github-oauth-provider` implementations behind its own Nginx
   server block.

Then:

1. GitHub → Settings → Developer settings → **OAuth Apps** → New.
   - Homepage URL: `https://electroserves.co.tz`
   - Authorization callback URL: your broker's callback.
2. Store the client ID and secret **in the broker**, never in this repository or
   in `public/admin/config.yml` (it is served to browsers).
3. Give editors **write** access to the repository — the CMS acts as the user.
4. Verify: open `https://electroserves.co.tz/admin`, log in, make a trivial edit,
   confirm a PR appears, merge it, deploy, confirm the change is live.

`/admin` gets a wider CSP than the public site (Decap loads from a CDN and calls
the GitHub API); see `SecurityHeadersMiddleware::contentSecurityPolicy()`. The
public pages keep the strict policy.

> Because merged content still has to be deployed to appear, decide who runs the
> deployment after a CMS merge — otherwise editors will report that publishing
> "does nothing".

---

## 10. Backup and restore

With no database, the backup set is small:

| Item | Where | How |
|---|---|---|
| Code and content | GitHub | Already replicated; mirror it if GitHub is your only copy. |
| `.env` | `shared/.env` | **The only irreplaceable file.** Contains `APP_KEY` and SMTP credentials. |
| TLS certificates | `/etc/letsencrypt/` | Re-issuable, but backing up avoids rate limits. |
| Nginx config | `/etc/nginx/sites-available/electroserves` | |
| Logs | `shared/storage/logs`, `/var/log/nginx` | Retain per your policy. |

```bash
#!/usr/bin/env bash
# /usr/local/bin/electroserves-backup.sh  (run daily via cron, as root)
set -euo pipefail
DEST=/var/backups/electroserves
STAMP=$(date +%Y%m%d)
mkdir -p "$DEST"

tar czf "$DEST/config-$STAMP.tar.gz" \
    /var/www/electroserves/shared/.env \
    /etc/nginx/sites-available/electroserves \
    /etc/letsencrypt

find "$DEST" -name 'config-*.tar.gz' -mtime +30 -delete
```

```bash
sudo chmod 700 /var/backups/electroserves     # it contains APP_KEY and SMTP credentials
```

Copy the archive off the machine. A backup that only exists on the server being
backed up is not a backup.

### Restore

```bash
sudo tar xzf /var/backups/electroserves/config-YYYYMMDD.tar.gz -C /
cd /var/www/electroserves
git clone https://github.com/Fstacklytics/electroserves.git repo
./deploy.sh main
sudo nginx -t && sudo systemctl reload nginx
```

Test a restore onto a scratch host at least once. An untested restore procedure
is a guess.

---

## 11. Health and smoke checks

The application exposes `/up`, which returns `200` when the framework boots.

```bash
curl -fsS https://electroserves.co.tz/up > /dev/null && echo "healthy"
```

Post-deploy smoke test — run this every time:

```bash
#!/usr/bin/env bash
# /usr/local/bin/electroserves-smoke.sh
set -uo pipefail
BASE="${1:-https://electroserves.co.tz}"
FAIL=0

check() {  # path expected_status
  code=$(curl -sS -o /dev/null -w '%{http_code}' "$BASE$1")
  if [ "$code" = "$2" ]; then echo "ok   $1 ($code)"
  else echo "FAIL $1 (got $code, want $2)"; FAIL=1; fi
}

check /up                 200
check /                   200
check /services           200
check /projects           200
check /blog               200
check /about              200
check /testimonials       200
check /faq                200
check /contact            200
check /privacy-policy     200
check /terms              200
check /sitemap.xml        200
check /robots.txt         200
check /this-page-does-not-exist 404
check /styleguide         404      # must NOT be reachable when APP_ENV=production
check /.env               404
check /storage/logs/laravel.log 404

echo "-- headers --"
curl -sI "$BASE/" | grep -Ei 'strict-transport|content-security|x-frame|x-content-type|referrer-policy' \
  || { echo "FAIL security headers missing"; FAIL=1; }

echo "-- redirect --"
loc=$(curl -sI "http://${BASE#https://}/" | awk '/^[Ll]ocation:/{print $2}' | tr -d '\r')
case "$loc" in https://*) echo "ok   http -> $loc";; *) echo "FAIL http redirect: $loc"; FAIL=1;; esac

exit $FAIL
```

Also verify manually after the first deploy:

- Submit the contact form and confirm the mail arrives at
  `CONTACT_NOTIFICATION_EMAIL`.
- Submit it six times in an hour and confirm the sixth is rejected with the
  rate-limit message, not a 500.
- Load a page with JavaScript disabled: content, navigation, pagination and the
  contact form must all still work.
- Confirm `/build/` assets return `Cache-Control: ... immutable`.

---

## 12. Rollback

Because a release is a directory and activation is a symlink, rollback is a
symlink flip plus an FPM reload.

Save as `/var/www/electroserves/rollback.sh`, `chmod +x`:

```bash
#!/usr/bin/env bash
set -euo pipefail

APP_DIR=/var/www/electroserves
CURRENT=$(readlink -f "$APP_DIR/current")
TARGET="${1:-}"

if [ -z "$TARGET" ]; then
  # Most recent release that is not the current one.
  TARGET=$(ls -1dt "$APP_DIR"/releases/*/ | grep -v "^$CURRENT/$" | head -n1)
fi

TARGET=$(readlink -f "$TARGET")

[ -d "$TARGET" ]              || { echo "No such release: $TARGET"; exit 1; }
[ -f "$TARGET/public/index.php" ] || { echo "$TARGET is not a valid release"; exit 1; }
[ "$TARGET" != "$CURRENT" ]   || { echo "$TARGET is already current"; exit 1; }

echo "Rolling back: $CURRENT -> $TARGET"
ln -sfn "$TARGET" "$APP_DIR/current"
sudo systemctl reload php8.3-fpm

cd "$APP_DIR/current"
php artisan content:flush

curl -fsS https://electroserves.co.tz/up > /dev/null \
  && echo "Rollback complete and healthy." \
  || { echo "WARNING: /up is not responding after rollback."; exit 1; }
```

```bash
./rollback.sh                                   # previous release
./rollback.sh releases/20260810091500           # a specific one
ls -1dt /var/www/electroserves/releases/*/      # what is available
```

Caveats:

- Rolling back reverts `content/` too, since content ships with the release.
  Content merged through the CMS since that release will disappear until you
  roll forward.
- `.env` and `storage/` are shared and are **not** reverted. If the bad deploy
  came with an `.env` change, undo that by hand and re-run
  `php artisan config:cache`.
- Only the last 5 releases are kept (`KEEP` in `deploy.sh`). Older ones require
  a fresh deployment of that Git ref.

---

## 13. Failure recovery

**Deployment failed part-way.** The symlink is only flipped after the build
succeeds, so a failure before that point leaves the site serving the previous
release untouched. Delete the half-built release directory and fix forward:

```bash
rm -rf /var/www/electroserves/releases/<timestamp>
```

**Site is down after a deploy.** Roll back first (§12), diagnose afterwards.

**502 Bad Gateway.** Nginx cannot reach PHP-FPM.

```bash
sudo systemctl status php8.3-fpm
ls -l /run/php/php8.3-fpm.sock        # must match fastcgi_pass
sudo tail -50 /var/log/nginx/electroserves.error.log
```

**500 on every page.** Usually a missing required env var (the boot-time
validator throws) or unwritable storage.

```bash
tail -100 /var/www/electroserves/shared/storage/logs/laravel.log
php artisan about                     # shows env, cached config, key status
ls -ld /var/www/electroserves/shared/storage/framework/{cache,views,sessions}
```

**Changes to `.env` have no effect.** A config cache is in place. Re-run
`php artisan config:cache`.

**Old code keeps being served.** `opcache.validate_timestamps=0` without an FPM
reload. `sudo systemctl reload php8.3-fpm`.

**Stale content after a CMS merge.** Deploy the merge, then
`php artisan content:flush`.

**Contact form silently fails.** Check `MAIL_*`, then the log. If a relay is
rejecting mail it is recorded in `storage/logs/laravel.log`.

**Emergency maintenance mode.**

```bash
php artisan down --retry=60
# ... fix ...
php artisan up
```

---

## 14. Monitoring, logs and incident response

| Log | Path |
|---|---|
| Application | `/var/www/electroserves/shared/storage/logs/laravel.log` |
| Nginx access | `/var/log/nginx/electroserves.access.log` |
| Nginx error | `/var/log/nginx/electroserves.error.log` |
| PHP-FPM | `/var/log/php8.3-fpm.log` |

Rotate the application log (`/etc/logrotate.d/electroserves`):

```
/var/www/electroserves/shared/storage/logs/*.log {
    daily
    rotate 14
    compress
    delaycompress
    missingok
    notifempty
    create 0644 www-data www-data
}
```

Monitor, at minimum:

- `https://electroserves.co.tz/up` every minute from outside the network.
- TLS certificate expiry (alert at 14 days).
- Disk usage on `/var` — the log and the file caches are what grow.
- HTTP 5xx rate in the Nginx access log.

```bash
# error rate over the last 1000 requests
tail -1000 /var/log/nginx/electroserves.access.log | awk '{print $9}' | sort | uniq -c | sort -rn

# rate-limited contact attempts
grep -c 'Contact form rate limit exceeded' \
  /var/www/electroserves/shared/storage/logs/laravel.log
```

### Incident response

1. **Assess** — is it down, degraded, or a security event? Check `/up`, then logs.
2. **Stabilise** — roll back (§12) or `php artisan down`. Restoring service comes
   before root cause.
3. **Preserve evidence** for a suspected compromise: copy logs off the host
   *before* redeploying.
4. **If credentials may be exposed**: rotate SMTP credentials and the GitHub
   OAuth client secret, then regenerate `APP_KEY` (this logs everyone out and
   invalidates encrypted cookies — acceptable here, as the site has no user
   accounts).
5. **Recover** — deploy the fix, run the smoke checks (§11).
6. **Write it up** — what broke, what was missing from the monitoring, what
   changes here.

---

## 15. Security and dependency audits

Run before every release, from a checkout with dev dependencies installed:

```bash
composer audit                 # known CVEs in PHP dependencies
composer outdated --direct     # direct dependencies behind latest

npm audit --omit=dev           # runtime JS advisories
npm audit                      # includes the build toolchain
npm outdated
```

Full local verification (matches CI):

```bash
vendor/bin/phpunit                                     # full test suite
php -l app/**/*.php                                    # syntax
php artisan view:cache && php artisan view:clear       # Blade compiles
npm run lint:js
npm run lint:css
npm run build
git diff --check                                       # whitespace errors
```

Periodically confirm no secret has been committed:

```bash
git log -p --all -- .env                               # must return nothing
grep -rnE '(APP_KEY|MAIL_PASSWORD|SECRET|TOKEN)\s*=\s*\S' \
     --include='*.php' --include='*.md' --include='*.yml' . \
     | grep -v '\.env\.example'
```

Also keep the OS patched (`sudo unattended-upgrades`), and review who has write
access to the GitHub repository — with the CMS backend, repository write access
is publish access.

---

## 16. Production readiness checklist

No secrets are required to complete this list; every item is a check, not a value.

**Application**

- [ ] `APP_ENV=production`
- [ ] `APP_DEBUG=false`
- [ ] `APP_URL` is the canonical `https://` origin, no trailing slash
- [ ] `APP_KEY` generated on this server and backed up
- [ ] `LOG_LEVEL=warning` or stricter
- [ ] `RESPONSE_CACHE_ENABLED=true`
- [ ] `CONTACT_NOTIFICATION_EMAIL` is a monitored mailbox
- [ ] `php artisan about` shows config, routes and views cached

**Server**

- [ ] Docroot is `current/public`, not the project root
- [ ] `.env` is `chmod 600` and outside the docroot
- [ ] Only `storage/` and `bootstrap/cache` are writable by `www-data`
- [ ] `content/` is not writable by `www-data`
- [ ] `expose_php = Off`, `display_errors = Off`
- [ ] FPM socket path matches `fastcgi_pass`
- [ ] `sudo nginx -t` passes
- [ ] Firewall allows only 22, 80, 443

**TLS and headers**

- [ ] HTTP redirects to HTTPS with 301
- [ ] Only TLS 1.2/1.3 negotiate
- [ ] Certificate valid; renewal timer active; deploy hook reloads Nginx
- [ ] `Strict-Transport-Security`, `Content-Security-Policy`, `X-Frame-Options`,
      `X-Content-Type-Options` and `Referrer-Policy` present on `/`
- [ ] No `Access-Control-Allow-Origin: *` anywhere

**Behaviour**

- [ ] Smoke script (§11) passes end to end
- [ ] `/styleguide` returns 404
- [ ] `/.env` and `/storage/logs/laravel.log` return 404
- [ ] Contact form delivers mail; the 6th submission in an hour is rate-limited
- [ ] Site is usable with JavaScript disabled
- [ ] `/build/` assets are served `immutable`; other statics revalidate
- [ ] `X-Response-Cache: HIT` on a second request to `/`; never on `/contact`

**Operations**

- [ ] `deploy.sh`, `rollback.sh` and the backup script are installed and executable
- [ ] A rollback has been tested at least once
- [ ] A restore has been tested onto a scratch host
- [ ] Uptime and certificate-expiry monitoring are alerting somewhere a human reads
- [ ] Log rotation is configured
- [ ] Decap CMS login works and a test edit reached production
