# Technology Decision Log — ElectroServes

---

## Revision note — Path B (Astro + Netlify)

> This decision log was originally recorded against a **Nginx + PHP-FPM 8.3 +
> Ubuntu 22.04** stack (Laravel 11, Blade, request-time Markdown/YAML parsing).
> That runtime is **replaced** by Path B: a **fully static Astro build
> deployed to Netlify**. See `docs/DEPLOY-NETLIFY-PATH-B.md` for the trade-offs
> and `docs/DEPLOY-VERCEL-ASSESSMENT.md` for why Vercel was assessed and not
> chosen. All content (Markdown + YAML), Tailwind design tokens, Alpine
> components and the Decap CMS collection definitions are unchanged.

---

## Summary Table

| Category | Technology | Version | Justification |
|---|---|---|---|
| **Language** | TypeScript / JavaScript | Node 20 LTS | Astro static build; no server runtime to patch or scale |
| **Framework** | Astro | 4.x (static) | File-based content collections, islands, zero JS by default |
| **Template Engine** | Astro components | (Astro built-in) | Compile-time rendering; component/slot support like Blade |
| **CMS** | Decap CMS | Latest (CDN) | Git-based; open-source; no database; clean admin UI |
| **CSS Framework** | Tailwind CSS | 3.x+ | Utility-first; design tokens; responsive; small production bundles |
| **JS Framework** | Alpine.js | 3.x+ | Lightweight; inline interactivity; no heavy build step |
| **Build Tool** | Vite (via Astro) | 5.x+ | Fast HMR; Astro uses Vite for bundling CSS + JS |
| **Content Format** | Markdown + YAML frontmatter | — | Human-readable; Git-friendly; Decap CMS native |
| **Config Format** | YAML | — | For structured data (settings, testimonials, FAQs, team) |
| **Git Hosting** | GitHub | — | Code + content hosting; Decap CMS via Git Gateway; Actions for CI |
| **CI/CD** | GitHub Actions | — | `npm ci && npm run build`; deploy to Netlify |
| **Web Server** | Netlify (static/CDN) | — | Global CDN, TLS, edge caching; no Nginx to maintain |
| **PHP Handler** | — | — | Removed — no PHP runtime in Path B |
| **SSL/TLS** | Netlify-managed TLS | — | Automatic certificates and renewal |
| **Server OS** | — | — | Removed — Netlify-managed infrastructure |
| **Server Management** | Netlify | — | Build + deploy managed by Netlify |
| **Image Optimization** | Astro Image (optional) | — | Built-in; content currently ships local placeholders |
| **Markdown Parser** | `@astrojs/markdown-remark` | (Astro-bundled) | Compile-time; rehype-sanitize strips raw HTML |
| **YAML Parser** | `yaml` (npm) + zod | — | Build-time validation at the content boundary |
| **Font** | Inter / Google Fonts | — | Modern, readable; good for UI text |
| **Icons** | Inline SVG (Heroicons-style) | — | No icon font or external request |
| **Analytics** | Plausible (optional) | — | Privacy-friendly; lightweight; no cookies |
| **Error Tracking** | Netlify build logs | — | No runtime errors — content is validated at build |
| **Contact Form** | Netlify Forms + honeypot | — | No server mailer, no app rate limiter (see below) |
| **Email Provider** | Netlify Forms notifications | — | Submissions delivered to a configured inbox |

---

## Why Astro (Path B) over alternatives?

| Criterion | Astro (Path B) | Laravel (old) | Next.js | Vercel static |
|---|---|---|---|---|
| File-based content reading | ★★★★★ | ★★★★★ | ★★★★ | ★★★★ |
| Server requirements | Static — none | Simple (PHP) | Node.js runtime | Static |
| CMS integration ease | ★★★★ | ★★★★★ | ★★★★ | ★★★★ |
| Long-term maintenance | ★★★★★ | ★★★★ | ★★★ | ★★★★ |
| Deployment model | Static build | Long-lived VM | Serverless/static | Static |
| Performance | ★★★★★ | ★★★★ | ★★★★★ | ★★★★★ |

Path B removes the request-time PHP runtime entirely: content is parsed and
validated **once, at build time**, and served as static HTML. That eliminates
the blockers recorded in `docs/DEPLOY-VERCEL-ASSESSMENT.md` (file-backed
sessions, file-backed response cache, request-time Markdown parsing, discarded
Nginx hardening, ephemeral logs and the cache-backed 5/hour rate limiter that
fails open on a stateless platform). Netlify Forms replaces the contact flow and
its honeypot replaces the rate limiter as the spam control.

### Why Decap CMS over alternatives?

| Criterion | Decap CMS | WordPress | Strapi | Statamic |
|---|---|---|---|---|
| Cost | Free | Free (hosting costs) | Free (hosting costs) | $259+/site |
| Database required | No | Yes (MySQL) | Yes (SQLite/PG) | Optional |
| Git-native | Yes | No | No | Optional |
| Admin UI quality | ★★★★ | ★★★★★ | ★★★★ | ★★★★★ |
| Content portability | ★★★★★ | ★★ | ★★★ | ★★★★ |
| Setup complexity | Low | Medium | Medium | Low |
| Community activity | Moderate | Very high | High | Moderate |

---

## Dependency Management

| Tool | Purpose | Configuration |
|---|---|---|
| **npm** | JavaScript/CSS dependency management | `package.json` with version constraints |
| **Dependabot** | Automated security updates | `.github/dependabot.yml` — weekly checks |
| **Astro + Vite** | Asset bundling integration | `astro.config.mjs`, `postcss.config.mjs` |

> Composer / Packagist is no longer used — the PHP runtime, its lock file and
> `composer audit` are removed. `npm audit` is the dependency audit.

---

## Version Pinning Strategy

- **Node.js**: `20` (LTS) specified in `.nvmrc`
- **Astro**: `^4.16` in `package.json`
- **Tailwind CSS**: `^3.4` in `package.json`
- **Alpine.js**: `^3.14` in `package.json`
- **Decap CMS**: CDN version pinned in `public/admin/index.html` (update manually on security releases)

---

*Document Version: 2.0 (Path B — Astro + Netlify)*
*Date: 2026-08-11*
