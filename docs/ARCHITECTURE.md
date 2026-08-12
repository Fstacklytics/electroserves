# Current Architecture — ElectroServes (Path B)

**Status:** Current / canonical. Last verified against the repository on
2026-08-12.

This is the authoritative description of the deployed stack. The previous
Laravel / Nginx + PHP-FPM architecture has been retired; see the
[Historical documents](#historical-documents) section for where those records
live and how they are labelled.

> **There is no application server.** Content is read from Markdown/YAML files
> and rendered to static HTML **once, at build time**. The output in `dist/` is
> deployed to Netlify's global CDN.

---

## 1. Technology stack

| Layer | Technology | Version (from `package.json` / `package-lock.json`) |
|---|---|---|
| Site framework | Astro (static output) | `astro ^4.16` (installed 4.16.x) |
| Language | TypeScript / JavaScript, ESM | Node 20 LTS (`.nvmrc`, `engines.node >=20`) |
| CSS | Tailwind CSS (via PostCSS + Autoprefixer) | `tailwindcss ^3.4` (3.4.x) |
| Client interactivity | Alpine.js (+ `@alpinejs/focus`, `@alpinejs/collapse`) | `alpinejs ^3.14` |
| Content parsing | `gray-matter`, `yaml`, `zod` | 4.x / 2.x / 3.x |
| Markdown rendering | Astro's bundled `@astrojs/markdown-remark` + `rehype-sanitize ^6` | Astro-bundled |
| CMS | Decap CMS (pinned CDN build) | `decap-cms@3.3.3` in `public/admin/index.html` |
| CMS auth | Netlify Identity + Git Gateway | configured in the Netlify dashboard |
| Forms | Netlify Forms (honeypot) | configured in markup, no app code |
| Deployment | Netlify (build + CDN + TLS) | free **Starter** plan; no upgrade required |
| CI | GitHub Actions | `.github/workflows/ci.yml` |
| Database | **None** | content is Markdown + YAML in Git |

Build tooling is Astro (which uses Vite internally); the old Laravel Vite
plugin and `public/build/` manifest are gone. Build assets are emitted to
`dist/_assets/` with content hashes (`astro.config.mjs` → `build.assets`).

---

## 2. Repository structure

```
.
├── astro.config.mjs          # Astro static config (site URL, _assets, preview host)
├── netlify.toml              # build command, publish dir, headers, CSP
├── package.json              # npm scripts + dependencies (Node 20, Astro 4)
├── tailwind.config.js        # design tokens (colors, spacing, min-h-touch)
├── postcss.config.mjs        # Tailwind + Autoprefixer
├── tsconfig.json
├── content/                  # ALL CMS-managed content (Markdown + YAML) — see §5
├── src/
│   ├── content/config.ts     # zod schemas for every content collection
│   ├── lib/                  # content loader, markdown, SEO, i18n helpers
│   ├── i18n/en.json          # user-facing UI strings
│   ├── components/           # ui/, sections/, common/, icons/ (Astro)
│   ├── layouts/Base.astro    # HTML shell: SEO head, nav, footer, client script
│   ├── js/client.ts          # browser entry; dynamically imports resources/js/app.js
│   ├── styles/global.css     # @Tailwind directives + base styles
│   └── pages/                # Astro routes (index, services, projects, blog, …)
├── resources/
│   ├── css/app.css           # Tailwind layers / component classes (linted by stylelint)
│   └── js/                   # Alpine component factories (linted by ESLint)
│       ├── app.js            # Alpine bootstrap + plugins
│       └── components/       # accordion, carousel, contact-form, modal, …
├── public/
│   ├── admin/
│   │   ├── index.html        # Decap CMS host page (loads pinned CDN bundle)
│   │   └── config.yml        # Decap collections, git-gateway backend, media paths
│   ├── uploads/              # CMS-uploaded media (committed by Decap/Git Gateway)
│   ├── favicon.ico
│   └── robots.txt
├── docs/                     # this file + deployment guides + historical phase docs
└── dist/                     # build output (gitignored; published by Netlify)
```

There is no `app/`, `bootstrap/`, `config/`, `routes/`, `database/`, `lang/`,
`resources/views/`, `storage/`, or `composer.json` — those belonged to the
retired Laravel build.

---

## 3. Local development

Prerequisites: **Node 20 LTS** and npm (the `.nvmrc` pins `20`).

```bash
npm ci                # clean, reproducible install (use npm install if no lockfile workflow)
npm run dev           # Astro dev server at http://localhost:4321
npm run build         # static build into dist/
npm run preview       # serve the production build locally
npm run lint:css      # stylelint "resources/css/**/*.css"
npm run lint:js       # eslint resources/js --ext .js
```

The Astro dev server and `preview` accept any Host header
(`vite.preview.allowedHosts: true` in `astro.config.mjs`) so sandbox/live
preview hosts work.

### Decap CMS locally

`public/admin/config.yml` sets `local_backend: true`. To exercise the CMS
against the local filesystem:

```bash
npm install -g netlify-cli   # one-time
npm run build                # netlify dev serves the built site
netlify dev                  # http://localhost:8888 → open /admin
```

The local backend proxy writes edits directly to `content/` and `public/uploads/`
without going through Git Gateway or Identity. On the deployed site the CMS
uses the `git-gateway` backend against `main` with
`publish_mode: simple` (every **Save** commits straight to `main` and triggers
the Production build; hiding an entry is the **Published** toggle, not an
Unpublish action — see below).

---

## 4. Build and rendering

- `npm run build` runs `astro build` with `output: 'static'`.
- Every route in `src/pages/**/*.astro` is pre-rendered to HTML in `dist/`
  (27 pages at the time of writing, including a dynamic sitemap).
- `src/lib/content.ts` reads `content/` with `gray-matter` + `yaml`, validates
  each file against the zod schemas in `src/content/config.ts`, and supplies
  typed collections to pages. A malformed/missing-required-field file is logged
  and skipped — it produces an empty state, never a build failure or a 500.
  YAML collections (`hero`, `testimonials`, `team`, `faqs`) are globbed as
  `*.yml`. Decap must set `extension: yml` and `format: yml` on those folders
  or it will publish `*.md` files the loader never sees (that is what happened
  to the first CMS hero in PR #18). The loader also accepts a `*.md`
  frontmatter fallback and logs `[content:warn]`. See
  [`NEXT-SESSION-CONTENT-NOT-LIVE.md`](NEXT-SESSION-CONTENT-NOT-LIVE.md).
- Markdown bodies are rendered by Astro's markdown pipeline with
  `rehype-sanitize`, so raw HTML in content is stripped rather than executed.
- Client JS is Alpine only. `src/layouts/Base.astro` loads it through a hoisted
  `<script>` that imports `src/js/client.ts`, which dynamically imports
  `resources/js/app.js` (the dynamic import keeps Alpine's MutationObserver
  touch out of the static-generation phase).
- Hashed JS/CSS under `dist/_assets/` are served with
  `Cache-Control: public, max-age=31536000, immutable`.

---

## 5. Content collections

All editable content lives as plain files under `content/`. The collection
shape is defined twice (deliberately): for editors in
`public/admin/config.yml`, and at the build boundary in
`src/content/config.ts`.

| Collection | Path | Format | Used by |
|---|---|---|---|
| Settings (site + SEO) | `content/settings/{site,seo}.yml` | YAML, single-file | layout/head, contact, footer |
| Hero slides | `content/hero/*.yml` | YAML (`extension: yml`) | homepage |
| Services | `content/services/*.md` | Markdown + frontmatter | `/services`, `/services/[slug]` |
| Projects | `content/projects/*.md` | Markdown + frontmatter | `/projects`, `/projects/[slug]` |
| Blog posts | `content/blog/YYYY-MM-DD-slug.md` | Markdown + frontmatter | `/blog`, `/blog/[slug]` |
| Testimonials | `content/testimonials/*.yml` | YAML (`extension: yml`) | homepage, `/testimonials` |
| Team | `content/team/*.yml` | YAML (`extension: yml`) | `/about` |
| FAQs | `content/faqs/*.yml` | YAML (answer is Markdown) | `/faq` |
| Static pages | `content/pages/{about,privacy-policy,terms}.md` | Markdown + frontmatter | `/about`, legal pages |

Uploaded media is committed to `public/uploads/` (`media_folder`) and served at
`/uploads/*` (`public_folder`). There is no object storage or database.

---

## 6. CMS, authentication and publishing

### Editor access

1. **Enable Netlify Identity** on the site (Site settings → Identity).
2. **Enable Git Gateway** (Identity → Services → Git Gateway); this grants the
   CMS scoped push access to the repository through Netlify, not a
   GitHub OAuth app.
3. **Invite editors** from the Identity tab. The Netlify **Starter (free)**
   plan covers up to 5 registered users — enough for the normal CMS workflow;
   no paid upgrade is required. Beyond 5 users Netlify charges per seat.

### Invitation / confirmation flow

- Netlify Identity invitation and confirmation emails link to the **site root**
  with a hash token, e.g. `https://electroserves.co.tz/#invite_token=...` (also
  `#confirmation_token=`, `#recovery_token=`, `#email_change=`).
- Only the Netlify Identity widget can consume those tokens, so
  `src/pages/index.astro` loads
  `https://identity.netlify.com/v1/netlify-identity-widget.js` on the homepage.
- The login handler (`window.netlifyIdentity.on('login', …)`) redirects
  authenticated users to **`/admin/`**. Astro/Vite hoists that handler out of
  the page into an external module under `/_assets/*.js` rather than inlining
  it, which keeps the homepage HTML free of inline script.

### Deployed CMS

- `public/admin/index.html` is a static host page that loads the pinned Decap
  bundle `https://unpkg.com/decap-cms@3.3.3/dist/decap-cms.js` and the Identity
  widget, with a visible fallback message if the CDN bundle fails to load.
- Backend in `public/admin/config.yml`: `name: git-gateway`, `branch: main`,
  `publish_mode: simple`. Every **Save** commits directly to `main`; there is
  no editorial-workflow branch, so the **Ready / Publish / Unpublish** menu no
  longer appears. Hiding an entry is the `Published` boolean (Off = skipped by
  `src/lib/content.ts`), not an Unpublish action that rewrites a `cms/*` branch.
- YAML folder collections set `extension: yml` and `format: yml` so new
  entries match `src/lib/content.ts` (`*.yml` globs). Markdown collections
  set `extension: md` and `format: frontmatter`.
- `site_url` / `display_url` are the **only** hosts Decap “View live” opens.
  Collection `preview_path` values (e.g. `projects/{{fields.slug}}`) build the
  per-entry View URL on that host — HTTPS, no trailing slash.
  They must match the Netlify primary domain that actually serves this site.
  They are **not** inferred from the `/admin` tab’s hostname. See
  [`NEXT-SESSION-CONTENT-NOT-LIVE.md`](NEXT-SESSION-CONTENT-NOT-LIVE.md).
- An editor **Save**s a change directly to `main` (custom commit messages:
  `content(create|update|delete): …`). **Show / Hide** is the `Published`
  boolean on every folder collection — On includes the entry in the next build,
  Off skips it (`src/lib/content.ts` filters `published: false`). Saving
  triggers the Netlify **Production** build; the live site updates when that
  build is **Published** (content is static, so edits are not instant at request
  time). There is no **Ready / Publish / Unpublish** menu — that menu produced
  `API_ERROR: Update is not a fast forward` because it tried to update a stale
  `cms/*` branch. Leftover `cms/*` branches are pruned automatically by
  `.github/workflows/prune-cms-branches.yml` on every push to `main`.
- **Delete** is disabled (`delete: false`) on the Settings (single-file)
  collection and on the Pages collection (About / Privacy / Terms), so those
  structural and legal files cannot be removed. Other folder collections allow
  delete. Each folder collection also exposes **Visible / Hidden** `view_filters`
  built on the `published` field so editors can see what the next build will
  include.
- Production for this repo is Netlify site `zippy-kitten-7cad33`. The custom
  domain `electroserves.co.tz` is the intended primary host once DNS + TLS
  are connected in Domain management; until then `SITE_URL` and Decap
  `site_url` point at `https://zippy-kitten-7cad33.netlify.app`.

---

## 7. Contact form (Netlify Forms)

`src/pages/contact.astro` contains a plain progressive-enhancement form:

- `method="POST"`, `name="contact"`, `data-netlify="true"`.
- `netlify-honeypot="bot-field"` plus a visually hidden `bot-field` input —
  Netlify silently drops submissions that fill it; there is **no** app-level
  5/hour rate limiter anymore (that was part of the retired Laravel build).
- A hidden `form-name=contact` input so Netlify detects the static form at
  deploy time.
- Submissions are stored in the Netlify dashboard and forwarded to the
  notification address configured there. Form notification recipients are a
  **Netlify dashboard setting**, not a repository value.
- `resources/js/components/contact-form.js` adds UX only (live validation,
  character count); the form works without JavaScript.

---

## 8. Security headers and Content Security Policy

Headers are defined in `netlify.toml` (there is no Nginx or application
middleware in Path B).

### Public site (`/*`)

```
default-src 'self';
script-src 'self' https://identity.netlify.com;
style-src 'self' 'unsafe-inline' https://fonts.googleapis.com;
font-src https://fonts.gstatic.com;
img-src 'self' data:;
connect-src 'self'
```

Plus `X-Frame-Options: DENY`, `X-Content-Type-Options: nosniff`,
`Referrer-Policy: strict-origin-when-cross-origin`. The public policy is
deliberately strict — **no `'unsafe-eval'`**, and scripts are restricted to
same-origin plus the Netlify Identity host.

### Decap CMS (`/admin` and `/admin/*`)

The admin policy is scoped narrowly to the CMS paths and relaxes `script-src`
only where Decap needs it:

```
script-src 'self' 'unsafe-inline' 'unsafe-eval' https://identity.netlify.com https://unpkg.com;
connect-src 'self' https://identity.netlify.com; …
```

- The CMS bundle is loaded from `unpkg.com`, so that origin is allowed **only**
  under `/admin*`.
- **`'unsafe-eval'` is required only on `/admin*`.** Decap uses AJV to compile
  the `config.yml` JSON schema with `new Function()` at load time; without it
  the admin UI fails with "Error loading the CMS configuration / EvalError".
  The public-site policy stays free of `'unsafe-eval'`.
- Both `/admin` (Astro uses `trailingSlash: 'never'`) and `/admin/*` are
  covered.

Immutable hashed assets under `/_assets/*` get the one-year cache header.

---

## 9. Deployment and CI

- **Netlify** builds with `npm run build` and publishes `dist/` (both set in
  `netlify.toml`). Pushes/merges to `main` and Decap editorial PRs trigger
  deploys automatically.
- **GitHub Actions** (`.github/workflows/ci.yml`) run on every push to `main`
  and on pull requests: `npm ci`, `npm run lint:css`, `npm run lint:js`,
  `npm run build`, then uploads `dist/` as a build artifact.
- **Plan:** Netlify **Starter (free)**. The CMS/Identity/Git Gateway/Forms
  workflow described above runs on it; no upgrade is required for normal
  operation. Be aware of Starter limits (build minutes, bandwidth, form
  submissions/month, up to 5 Identity users) and that enabling a custom domain
  or high traffic may nudge against them.

### Repository vs. Netlify dashboard

| Operation | Where it lives |
|---|---|
| Build command, publish dir, headers, CSP, redirects | repository (`netlify.toml`) |
| Page/layout/component code, styles, Alpine JS | repository (`src/`, `resources/`) |
| Content, CMS collection schema | repository (`content/`, `public/admin/config.yml`) |
| Decap CDN version pin | repository (`public/admin/index.html`) |
| Enable/disable Identity, invite/remove editors, reset passwords | Netlify dashboard |
| Enable Git Gateway, connect the repository | Netlify dashboard (one-time) |
| Form notification email address, spam settings | Netlify dashboard |
| Custom domain, TLS, primary domain, visitor access / team protection | Netlify dashboard |
| Identity email templates | Netlify dashboard (out of scope for repo changes) |
| Billing / plan selection | Netlify dashboard (do not change — remains Starter) |

---

## 10. Historical documents

The following files record the **retired Laravel design and its phases**. They
are retained as history, each carrying a prominent superseded banner — do **not**
follow their commands as current instructions:

- `docs/PHASE-1-SUMMARY.md`, `docs/PHASE-2-SUMMARY.md`,
  `docs/PHASE-4-GAP-MATRIX.md` — phase summaries of the Laravel build.
- `docs/IMPLEMENTATION-PROMPT.md` — original four-phase Laravel implementation
  prompt.
- `docs/NEXT-SESSION-PROMPT.md` — completion handoff for the Laravel phases.
- `docs/phase-0/01–09*.md` — original problem statement, user flows, ADRs,
  data model, threat model, SLOs, etc., written against Nginx + PHP-FPM.
- `docs/phase-0/10-decap-cms-config.yml` — historical copy of the CMS config;
  the live config is `public/admin/config.yml`.
- `docs/DEPLOY-VERCEL-ASSESSMENT.md` — historical analysis of Vercel against
  the old Laravel codebase (already superseded).

`docs/phase-0/07-technology-decision-log.md` has been revised to describe Path B
and should be treated as current for stack decisions. `docs/DEPLOY-NETLIFY-PATH-B.md`
is the current deployment/trade-off record alongside this file.
`docs/NEXT-SESSION-CONTENT-NOT-LIVE.md` is the current CMS publishing /
domain / View-live runbook.
