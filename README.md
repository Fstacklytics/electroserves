# ElectroServes Website

> A modern, accessible website for an electronics and electrical services
> company, built as a **static site** with Astro + Tailwind + Alpine and
> Decap CMS, deployed to **Netlify** (Path B).

---

## Tech Stack

| Layer | Technology |
|---|---|
| Framework | Astro 4 (static output) |
| Language | TypeScript / JavaScript (Node 20 LTS) |
| CSS | Tailwind CSS 3 (design tokens in `tailwind.config.js`) |
| Interactivity | Alpine.js 3 (`resources/js/`) |
| CMS | Decap CMS (Netlify Identity + Git Gateway) |
| Content Storage | Markdown + YAML files in Git (`content/`) |
| Content validation | zod at the build-time boundary (`src/content/config.ts`) |
| Forms | Netlify Forms + honeypot |
| Build Tool | Astro / Vite (`astro.config.mjs`, `postcss.config.mjs`) — assets in `dist/_assets/` |
| CI | GitHub Actions (`.github/workflows/ci.yml`: `npm ci`, lint CSS/JS, build) |
| Deployment | Netlify static (free **Starter** plan; `netlify.toml`) |
| Database | None (file-based content) |

> This is **Path B** — the previous Laravel / Nginx + PHP-FPM runtime is removed.
> **Start with [`docs/ARCHITECTURE.md`](docs/ARCHITECTURE.md)** for the canonical
> description of the deployed stack. See `docs/DEPLOY-NETLIFY-PATH-B.md` for the
> migration trade-offs and `docs/DEPLOY-VERCEL-ASSESSMENT.md` (historical) for
> why Vercel was assessed against the old codebase and not chosen.

---

## Architecture

```
Content files (content/*)  ──┐
                              ├─►  build-time parse + zod validation  ──►  static HTML (dist/)
Netlify Identity + Git Gateway │
   (Decap CMS /admin)  ────────┘                 ▲
                                                 │ deployed
Visitor ──► Netlify CDN (static HTML + headers from netlify.toml)
```

There is no server runtime: content is parsed **once at build time** and served
as static files. Contact submissions go through **Netlify Forms** with a
honeypot field.

---

## Getting started

Prerequisites: **Node 20 LTS** and npm.

```bash
npm install
npm run dev        # local dev server (http://localhost:4321)
npm run build      # static build into dist/
npm run preview    # preview the built site
```

### Decap CMS locally

`public/admin/config.yml` sets `local_backend: true`, so the CMS can write to
the local filesystem through the Netlify CLI proxy:

```bash
npm install -g netlify-cli   # one-time
npm run build
netlify dev                  # http://localhost:8888 → open /admin
```

On the deployed site, an administrator enables **Identity → Git Gateway** in
the Netlify dashboard and invites editors; editors sign in at `/admin` with
Netlify Identity (up to 5 editors on the free Starter plan). Invitation and
confirmation emails link to the homepage, where the Identity widget processes
the token and redirects to `/admin/`. Saving content in the CMS opens a PR
(editorial workflow); merging to `main` triggers the Netlify build.

---

## Project structure

```
├── content/                  # ALL CMS-managed content (Markdown + YAML)
│   ├── settings/             # site.yml, seo.yml
│   ├── hero/ services/ projects/ blog/ testimonials/ team/ faqs/ pages/
├── src/
│   ├── content/config.ts     # zod schemas for every collection
│   ├── lib/                  # content loader, markdown, seo, i18n helpers
│   ├── i18n/en.json          # all user-facing strings
│   ├── components/           # ui/, sections/, common/, icons/
│   ├── layouts/Base.astro    # base layout (SEO head, nav, footer, toast)
│   └── pages/                # index, services, projects, blog, about, etc.
├── resources/
│   ├── css/app.css           # Tailwind base layers + design tokens
│   └── js/                   # Alpine components (app.js, carousel, modal, …)
├── public/
│   ├── admin/                # Decap CMS (index.html + config.yml)
│   └── robots.txt
├── netlify.toml              # build config + security headers
├── astro.config.mjs
└── tailwind.config.js
```

---

## Pages

| Page | Route | Content source |
|---|---|---|
| Homepage | `/` | `content/hero`, `content/services`, `content/testimonials` |
| Services | `/services` | `content/services/` |
| Service detail | `/services/{slug}` | `content/services/{slug}.md` |
| Projects | `/projects` | `content/projects/` |
| Project detail | `/projects/{slug}` | `content/projects/{slug}.md` |
| Blog | `/blog` | `content/blog/` |
| Blog post | `/blog/{slug}` | `content/blog/{slug}.md` |
| About | `/about` | `content/pages/about.md`, `content/team/` |
| Testimonials | `/testimonials` | `content/testimonials/` |
| Contact | `/contact` | `content/settings/site.yml` |
| FAQ | `/faq` | `content/faqs/` |
| Privacy policy | `/privacy-policy` | `content/pages/privacy-policy.md` |
| Terms | `/terms` | `content/pages/terms.md` |
| CMS admin | `/admin` | Decap CMS |

---

## Design system

- **Colors:** primary (blue), secondary (amber), neutral (slate), success / warning / danger / info — defined in `tailwind.config.js`.
- **Typography:** Inter (self-hosted-friendly, `font-display: swap`), JetBrains Mono for code.
- **Spacing:** 4 px base, `min-h-touch` (44 px) accessible touch targets.
- **Accessibility:** WCAG 2.1 AA, single focus ring (`:focus-visible`), skip link, labelled inputs, 44×44 px targets, reduced-motion support.

---

## Testing / verification

The PHPUnit suite was removed with the Laravel runtime. The equivalent static
checks run in CI (`.github/workflows/ci.yml`):

```bash
npm run lint:css
npm run lint:js
npm run build
```

A build that fails validation logs the offending content file and renders its
fallback empty state rather than failing — see `src/lib/content.ts`.

---

## Documentation

- **[`docs/ARCHITECTURE.md`](docs/ARCHITECTURE.md)** — canonical description of
  the current Path B stack (rendering, content collections, CMS/Identity,
  forms, CSP, deployment, what lives in the repo vs. the Netlify dashboard).
- [`docs/DEPLOY-NETLIFY-PATH-B.md`](docs/DEPLOY-NETLIFY-PATH-B.md) — migration
  to Netlify and the trade-offs versus the retired VPS deployment.
- [`docs/phase-0/07-technology-decision-log.md`](docs/phase-0/07-technology-decision-log.md)
  — current technology decision record (revised for Path B).

Files under `docs/PHASE-*.md`, `docs/IMPLEMENTATION-PROMPT.md`,
`docs/NEXT-SESSION-PROMPT.md`, most of `docs/phase-0/`, and
`docs/architecture-diagram.svg` are **historical records of the retired
Laravel/Nginx design** and are marked as superseded. Do not follow their
commands as current instructions; see the "Historical documents" section of
`docs/ARCHITECTURE.md`.

*Last updated: 2026-08-12*
