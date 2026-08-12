# Publishing, “View live”, and why CMS edits are not instant

**Status:** Current. Written 2026-08-12 after Decap Ready → Publish of the
hero slide “We are here to serve you” did not appear on the public site.

This is the editor / operator companion to [`ARCHITECTURE.md`](ARCHITECTURE.md)
§6. It records the root-cause tree, what is fixed in the repo, and what can
only be done in the Netlify / registrar dashboards.

---

## How content reaches the public HTML

This site is **static Astro on Netlify**. There is no live database and no
request-time Markdown parse (`src/lib/content.ts` reads `content/` **only at
build time**).

```
Decap Save          → draft on a cms/* branch (editorial workflow)
Decap Ready         → editorial status only; still not on main
Decap Publish       → merge/commit to Git branch `main`
Netlify Production  → `npm run build` → `dist/` → CDN
```

**Publish in Decap ≠ instant page update.** Wait for a **green Production
deploy** of the commit that contains the file.

- Branch Deploys / Deploy Previews are **not** the production `*.netlify.app`
  site unless that is the production-branch deploy.
- `publish_mode: editorial_workflow` stays enabled. Save must not write
  straight to `main`.

---

## “View live” always opens `site_url`

In `public/admin/config.yml`, Decap’s **View live** / site link uses
`site_url` and `display_url`. It does **not** use the browser’s current host
and does **not** use “whatever `*.netlify.app` you opened `/admin` on”.

Per-entry **View** (a published project, service, blog post, or page) appends
that collection’s `preview_path` — for example
`projects/{{fields.slug}}` →
`https://zippy-kitten-7cad33.netlify.app/projects/kariakoo-retail-fitout`
(HTTPS, no trailing slash). Use that URL, not `http://…/projects/…/`.

| Host | Role (2026-08-12) |
|---|---|
| `https://zippy-kitten-7cad33.netlify.app` | Working production host for this repo (Netlify site `zippy-kitten-7cad33`) |
| `https://electroserves.co.tz` | Intended custom domain — **no public DNS** from 1.1.1.1 / 8.8.8.8 / 9.9.9.9 |
| `https://deploy-preview-N--zippy-kitten-7cad33.netlify.app` | PR preview only |

Until `electroserves.co.tz` is added in **Netlify → Domain management**, has
DNS + TLS, and is set as the **primary domain**, keep Decap `site_url` /
`display_url` and the `SITE_URL` build env on the Netlify subdomain.

When the custom domain is live, change **together**:

1. `public/admin/config.yml` → `site_url`, `display_url`
2. `netlify.toml` `[build.environment] SITE_URL` (or the Netlify UI env var)
3. `public/robots.txt` sitemap line

Optional `[[redirects]]` www ↔ apex in `netlify.toml` only after DNS works.

---

## Root-cause tree (check in this order)

### 1) Publish did not land on `main` (or wrong repo)

```bash
gh pr list --state all --search "content" --limit 20
git log origin/main -10 --oneline -- content/ public/uploads/
```

Look for `content(update):…` / `content(create):…` / `media(upload):…` on
**`main`**.

| Finding | Meaning |
|---|---|
| No content commit on `main` | Publish didn’t merge; still a draft PR or Git Gateway write failed |
| Commit on a `cms/*` branch only | Editorial PR not merged |
| Commit on `main` | Proceed to (2) |

**2026-08-12 evidence:** PR **#18**
(`content(create): Hero Slides — we-are-here-to-serve-you`) **was merged**
to `main` at `db8a4ab` (2026-08-12 06:17 UTC). Git Gateway and editorial
merge were **not** the blocker for that entry.

### 2) Production deploy did not run or failed

Netlify UI → Site → Deploys → **Production** (branch `main`).

| Finding | Meaning |
|---|---|
| No deploy after content commit | Build hook / repo link broken |
| Deploy failed | Open log; often `[content:error]` zod validation or Node version |
| Deploy **Published** with the correct commit SHA | HTML should contain the change on **that site’s** production URL |

GitHub Actions CI for `db8a4ab` was **green** (`31569483297`). Netlify
Production status is dashboard-only and was **not** verified from the sandbox.

### 3) Looking at the wrong hostname (also true here)

Decap **View live** opened `https://electroserves.co.tz/` while the team
checked `*.netlify.app`.

**2026-08-12 evidence:**

- `electroserves.co.tz` and `www.electroserves.co.tz` → **NXDOMAIN** (no A / AAAA / NS)
- Netlify site for this repo is **`zippy-kitten-7cad33`**
  (`https://zippy-kitten-7cad33.netlify.app`)
- That Netlify subdomain is behind **Team protection** (“This site is private —
  Sign in with an invited Netlify account”). Unauthenticated visitors never
  see the HTML.

So even a correct production build is invisible on the custom domain (no DNS)
and on the Netlify subdomain (visitor access) until those dashboard items
are fixed.

### 4) Identity / admin on one host, “live site” on another

Editors may open `/admin` on `https://zippy-kitten-7cad33.netlify.app/admin`
while `site_url` pointed at a domain that does not resolve. This PR points
`site_url` / `display_url` at the Netlify subdomain so View live matches the
host that exists.

Confirm in the dashboard that **Identity + Git Gateway** are enabled on
**this** site (`zippy-kitten-7cad33`), and that only one Netlify site is
linked to `Fstacklytics/electroserves`.

### 5) Content filtered out at build — **this was the code bug**

`src/lib/content.ts` loads hero / testimonials / team / FAQs with
`import.meta.glob('…/*.yml')`. Invalid frontmatter is logged and skipped;
`published: false` is skipped.

Decap folder collections **default to Markdown (`.md`)** unless `extension`
and `format` are set. PR #18 therefore committed:

```
content/hero/we-are-here-to-serve-you.md    ← not loaded
public/uploads/electric.png                 ← present, unused by the missed slide
```

The file was valid, `published: true`, and on `main`. The build never opened
it, so the homepage kept the three seed slides. GitHub CI stayed green
because skipped / unseen files are not a build failure.

The same trap would hit any **new** testimonial, team member, or FAQ created
in the CMS.

---

## What this repository now does

| Change | Why |
|---|---|
| Convert the published hero to `content/hero/we-are-here-to-serve-you.yml` | So the existing publish is actually loaded |
| `extension: yml` + `format: yml` on hero, testimonials, team, FAQs | Future Decap creates match the loader |
| `extension: md` + `format: frontmatter` on services, projects, blog, pages | Explicit; prevents the inverse mistake |
| YAML loaders also accept `*.md` frontmatter as a fallback (with a `[content:warn]`) | In-flight editorial `.md` files still render |
| Decap `site_url` / `display_url` + `SITE_URL` + robots sitemap → Netlify subdomain | View live and canonicals match a host that exists |
| Collection `preview_path` (e.g. `projects/{{fields.slug}}`) | Per-entry View is HTTPS and has no trailing slash |
| Empty featured/gallery images no longer render photo frames | Caption-only items (e.g. “Roof-mounted array…”) show as highlights |

Unchanged on purpose: `publish_mode: editorial_workflow`, `media_folder` /
`public_folder`, public CSP, admin `blob:` allowlist (PR #17).

---

## Dashboard-only steps (cannot be done from Git)

Do these in order. The unique string to search for after a green deploy is:

`We are here to serve you`

1. **Netlify → Domain management** for site `zippy-kitten-7cad33`
   - Confirm the production URL is `https://zippy-kitten-7cad33.netlify.app`
   - Add `electroserves.co.tz` / `www` when ready; copy the DNS records
     Netlify shows to the registrar; wait for DNS + TLS **Issued**
   - Set the **primary domain** intentionally
   - Only one Netlify site should be linked to this GitHub repo
2. **Netlify → Project configuration → Access / Visitor access**
   - Turn **off** Team protection / password protection if the marketing
     site should be public. Today the `*.netlify.app` host presents
     “This site is private”.
3. **Netlify → Deploys → Production**
   - Confirm a **Published** deploy of the commit that contains the `.yml`
     hero (this PR, once merged). If missing/failed → Redeploy or fix the log.
4. **Identity → Git Gateway**
   - Still enabled, still this site, still this repo.
5. **Open both URLs in a private window** after the green deploy and search
   page source for the unique string. Hard-refresh; HTML is not immutable-
   cached like `/_assets/*`.
6. **Ignore View live** until `site_url` matches a host that loads for the
   person clicking it.

---

## Editor checklist (no code)

1. Save → Ready → **Publish** in Decap.
2. GitHub → `main`: confirm the file/commit exists (not only in the CMS UI).
3. Netlify → Production deploy for that SHA is **Published** (green).
4. Open the **primary** URL from Domain management (today the Netlify
   subdomain; later `https://electroserves.co.tz` once DNS works).
5. Search the page source for the unique edited string.

---

## One-sentence diagnosis

**Publishing updates Git; the live site only changes after a successful
Netlify production build — Decap “View live” always opens `site_url`, and a
YAML collection entry saved as `.md` is invisible to the `*.yml` loader even
when the commit is on `main`.**
