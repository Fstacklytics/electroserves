# Vercel Deployment Assessment — ElectroServes

**Status:** Assessed 2026-08-11. **Verdict: Vercel is not supported and not recommended for this codebase in its current form.** A Vercel deployment would require a re-architecture of session, cache, content and hardening layers that are currently file-backed and Nginx-specific.

This document resolves a previously dangling reference to a Vercel assessment. It exists to record the analysis so future sessions do not re-investigate the same target.

## Canonical deployment target

`docs/phase-0/07-technology-decision-log.md` is explicit and is the decision record:

- **Web Server:** Nginx 1.24+
- **PHP Handler:** PHP-FPM 8.3+
- **OS:** Ubuntu 22.04 LTS
- Optional server management: Laravel Forge

`deploy/nginx.conf` and `deploy/README.md` (16 sections) implement that choice. Vercel is **absent** from the technology decision log and from `docs/phase-0/06-architecture-decisions.md`. The Phase 0 scope is a public, file-backed Laravel site on a long-lived VM, not a stateless serverless platform.

## Why Vercel cannot host this codebase as-is

### 1. File-backed sessions

`config/session.php` uses the `file` driver by default (`SESSION_DRIVER=file`). The session stores:

- CSRF token (`XSRF-TOKEN` / `electroserves_session` cookies)
- Contact-form honeypot timestamp for spam throttling
- Flash data for validation errors

Vercel Functions run on an ephemeral, read-only filesystem (except `/tmp`, which is per-invocation and not shared). Two successive requests may hit different lambdas. File sessions would be lost between requests, breaking CSRF, flash errors, and the contact flow. Replacing with `cookie`, `database` or `redis` requires a new driver, new tests and a new threat review.

### 2. File-backed response cache

`app/Services/ResponseCacheService.php` + `app/Http/Middleware/ResponseCacheMiddleware.php` are configured by the `response_cache` block in `config/electroserves.php`. The cache is built on Laravel's `Cache` facade; the default store is `file` (`storage/framework/cache`). The Phase 4 design also embeds a content fingerprint (paths + mtimes of `content/`) into the cache key so edits to Markdown/YAML invalidate implicitly.

On Vercel, the file cache would be ephemeral per lambda and unshared across edge regions. You would get 100% cache misses, or worse, inconsistent hits across regions. Moving to Redis/Upstash or Vercel KV demands a store change, key-prefix isolation and a rewrite of `content:flush` / `responsecache:clear`.

### 3. Request-time Markdown parsing

`ContentService`, `MarkdownService` (`league/commonmark`) and `YamlService` (`symfony/yaml`) read directly from `content/` on disk at request time, with a file-cache TTL fallback (`CONTENT_CACHE_ENABLED`, `CONTENT_CACHE_TTL`). There is no pre-build static export.

On Vercel, `content/` would be bundled into the lambda image, but mtime-based cache invalidation would not work reliably in a read-only image, and every cold start would pay the parse cost. A static-site-generation step or a build-time content compilation cache would be needed.

### 4. Discarded Nginx hardening

`deploy/nginx.conf` contains hardening that has no equivalent on Vercel's routing layer:

- `client_max_body_size` aligned with `post_max_size`
- `fastcgi_read_timeout` aligned with PHP `max_execution_time`
- `gzip` (+ optional Brotli) configuration
- `limit_req_zone` / `limit_req` for flood protection (deliberately looser than the app limiter so Laravel returns its own translated 429)
- CSP and other security headers are set only in `SecurityHeadersMiddleware` because a single `add_header` inside an Nginx `location` silently drops inherited headers — that invariant is asserted by `DeploymentConfigTest`. On Vercel, you would rely on `vercel.json` headers, which do not compose the same way and would duplicate the middleware.

Disabling this config removes protections that have test coverage.

### 5. Ephemeral logs

The app writes to `storage/logs` and expects `shared/storage` to survive deploys (see `deploy/README.md` directory layout). Vercel Functions have ephemeral logs drained to external services. File logs would disappear on every invocation. You would need to reconfigure `LOG_CHANNEL` to `stderr` / external and update the backup runbook.

### 6. Cache-backed 5/hour contact rate limiter which fails open silently

`App\Providers\RateLimitServiceProvider` implements the contact form limiter: max **5 submissions per IP per hour** (`CONTACT_RATE_LIMIT_MAX` / `CONTACT_RATE_LIMIT_DECAY`), backed by Laravel's `Cache`. The limiter sits behind the Nginx looser zones (30r/m, 120r/m) so Laravel returns a friendly, translated 429 instead of a bare Nginx error.

On Vercel, with a file or array cache, the counter would reset every invocation → the limiter would **fail open silently**, allowing unlimited spam. With a shared cache (Redis/Upstash), the limiter would work but only after introducing external state, latency and new secrets. The current `ThrottlesContacts` logic also logs transport failures; with ephemeral logging that audit trail is lost.

## What a supported deployment looks like (VPS checklist)

This is the path documented in `deploy/README.md` and already validated by 27 assertions in `DeploymentConfigTest`:

- [ ] Ubuntu 22.04 LTS, Nginx 1.18+ (1.25+ preferred for `http2 on;`), PHP-FPM 8.3, Node 20 LTS
- [ ] `shared/.env` with `APP_ENV=production`, `APP_DEBUG=false`, valid `APP_KEY`, `APP_URL=https://…`, SMTP credentials, `CONTACT_NOTIFICATION_EMAIL`
- [ ] Releases under `/var/www/electroserves/releases/<timestamp>`, `current` symlink, `shared/storage` persisting across deploys
- [ ] `content/` read-only to `www-data`, not shared between releases (rollback is code + content atomic)
- [ ] `php artisan config:cache`, `route:cache`, `view:cache`, `content:flush` + FPM reload on every deploy (`opcache.validate_timestamps=0`)
- [ ] `RESPONSE_CACHE_ENABLED=true`, optional `RESPONSE_CACHE_STORE=responses` with its own file store
- [ ] Nginx `sites-available/electroserves` from `deploy/nginx.conf`, `nginx -t` passing, `limit_req_zone` in `conf.d/` if enabled, `client_max_body_size` mapped
- [ ] TLS via Let's Encrypt, certbot timer verified, reload hook in place
- [ ] Decap CMS OAuth broker (Netlify/Cloudflare Pages or self-hosted proxy), not in this repo
- [ ] Backup of `shared/.env` (only irreplaceable file), `composer.lock` generated where Packagist is reachable, `composer audit` / `npm audit` clean
- [ ] `php -m` includes `mbstring`, `dom`, `curl`, `zip`, `fileinfo` (see §1 of deploy guide)
- [ ] Health checks: `curl -sI … | grep X-Response-Cache`, `/up` route, sitemap, robots, contact flow

## Untested Vercel-rework checklist (only if Vercel is explicitly decided)

Do **not** execute this unless the product owner amends `docs/phase-0/07-technology-decision-log.md` to include Vercel and accepts the trade-offs. This list is intentionally not validated — it is here to prevent a future session from underestimating the work:

- [ ] Replace `SESSION_DRIVER=file` with `cookie`, `redis` or Vercel-KV-backed session, audit CSRF and flash semantics, update `SecurityHeadersMiddleware` tests
- [ ] Replace `CACHE_STORE=file` and `RESPONSE_CACHE_STORE=file` with Redis / Upstash / Vercel KV, rework cache-key fingerprinting to avoid mtime (use Git SHA), update `ResponseCacheTest` to use the new store
- [ ] Replace contact rate limiter backing with shared store, prove it does not fail open under cold-start and multi-region concurrency, add dedicated rate-limit tests
- [ ] Reconfigure logging to `stderr` or external sink, remove reliance on `storage/logs` persistence
- [ ] Rewrite `deploy/nginx.conf` rules into `vercel.json` (headers, rewrites, `cleanUrls`, `trailingSlash`, body size limits), and re-implement flood protection that was previously in Nginx
- [ ] Build-time content pre-parse: generate a content manifest to avoid per-request Markdown/YAML parsing and mtime checks on a read-only filesystem
- [ ] Verify `public/build/manifest.json` handling under Vercel's build pipeline, Vite output, and asset immutability headers
- [ ] Re-run full suite (`vendor/bin/phpunit`, 498 tests / 2,786 assertions at Phase 4) on PHP 8.3 with the new stores, plus `composer audit`, `npm audit`, and `nginx -t` equivalents for `vercel.json`
- [ ] Manual browser + screen-reader passes (no browser in sandbox)
- [ ] Update `deploy/README.md` with Vercel-specific runbook and rollback (Vercel rollback is deployment-based, not symlink-based)

## Alternatives that preserve Nginx + PHP-FPM semantics

If the goal is managed hosting without re-architecting:

- **Laravel Forge + DigitalOcean / Hetzner / Linode** — provisions Ubuntu 22.04, Nginx, PHP-FPM 8.3, Let's Encrypt, deploys from GitHub, preserves `deploy/nginx.conf` and file-backed caches. Closest to the technology decision log.
- **Ploi.io** — similar to Forge, supports Ubuntu 22.04, PHP 8.3, Nginx, managed queues and cron.
- **RunCloud** — agent-based panel on any VPS, keeps Nginx + PHP-FPM, supports Git deploys.
- **Fly.io** (Docker) — run `Dockerfile` with Nginx + PHP-FPM 8.3 on Ubuntu 22.04, persistent volume for `shared/storage` if needed, region-local caching, keeps file-backed semantics without moving to serverless. More ops than Forge but less rework than Vercel.

---

## References

- `docs/phase-0/07-technology-decision-log.md` — canonical stack: Nginx + PHP-FPM 8.3 + Ubuntu 22.04. Vercel absent.
- `docs/phase-0/06-architecture-decisions.md` — ADRs for file-backed content, no DB, Decap CMS Git backend.
- `deploy/README.md` — 16-section runbook keyed to this repo's real paths.
- `deploy/nginx.conf` — hardening and rate-limit zones deliberately looser than app limiter.
- `app/Http/Middleware/SecurityHeadersMiddleware.php` — security headers live in app, not duplicated in Nginx.
- `app/Providers/RateLimitServiceProvider.php` — 5/hour per IP contact limiter, cache-backed.
- `config/electroserves.php` — `response_cache`, `required_env`, content TTLs.
