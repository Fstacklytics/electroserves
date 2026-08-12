# Phase 1 — Foundation: what was built

> ⚠️ **Historical / superseded — not current operating documentation.**
> This document records the retired **Laravel 11 / Nginx + PHP-FPM 8.3 / Ubuntu**
> design and its implementation phases. That runtime has been replaced by a
> **fully static Astro site deployed to Netlify (Path B)** — the code,
> directories, build/test commands, env vars, caches, middleware and
> deployment runbooks described below **no longer exist and must not be used**.
> For the current system see **[`docs/ARCHITECTURE.md`](ARCHITECTURE.md)** and
> [`docs/DEPLOY-NETLIFY-PATH-B.md`](DEPLOY-NETLIFY-PATH-B.md). This file is
> retained only as a historical design record.

---


Reference for anyone continuing the build. Conventions established here apply
to every later phase.

---

## Toolchain

| Layer | Version |
|---|---|
| PHP | 8.3 |
| Laravel | 11.55 |
| Tailwind CSS | 3.4 |
| Alpine.js | 3.14 (+ focus, collapse plugins) |
| Vite | 7 (upgraded from 5 to clear a dev-server advisory) |
| PHPUnit | 11.5 |
| league/commonmark | 2.x · symfony/yaml 7.x |

Run tests with `php vendor/bin/phpunit` (the `artisan test` command needs
`nunomaduro/collision`, which is not installed).

---

## Architecture

```
Request → SecurityHeadersMiddleware → Controller → ContentService → DataObjects → Blade
                                                        ↓
                                          content/*.md + *.yml (cached)
```

- **No database.** `config/database.php`, `config/queue.php` and `config/auth.php`
  were removed. `config/session.php` is kept because CSRF requires a session.
- **`config/electroserves.php`** holds every app-specific value: content paths,
  cache TTL, categories, contact-form limits, and the required-env list.
- Services are singletons registered in `AppServiceProvider`.
- `resources/views` is registered as an anonymous component path so
  `<x-layouts.app>` resolves to `resources/views/layouts/app.blade.php` while
  keeping the documented directory structure.

---

## The rules, and where they are enforced

| Rule | Implementation |
|---|---|
| No silent failures | `ContentService` / `YamlService` / `MarkdownService` log every failure and return `null` or an empty collection. Nothing throws to a controller. |
| Validate at boundaries | `ContactFormRequest` (forms) · `fromArray()` factories (content) · `EnvironmentValidationServiceProvider` (config) · `{{ }}` escaping (output) |
| Markdown safety | `html_input: strip` and `allow_unsafe_links: false` at parse time — injection is impossible, not filtered |
| Path safety | `ContentService::normaliseSlug()` and `ImageService::normalise()` reject traversal and non-http schemes |
| No hardcoded strings | Everything user-facing lives in `lang/en/*.php` |
| Accessibility | Labels on every input, `aria-describedby` error wiring, focus trapping via `x-trap`, 44px targets (`min-h-touch`), skip link, `prefers-reduced-motion` in CSS **and** JS |

---

## Conventions to follow

**Adding a content type**
1. DataObject in `app/DataObjects/` — readonly, `use ValidatesContent`, static
   `fromArray(): ?self` that validates required fields and returns `null`.
2. Loader method on `ContentService` using `loadMarkdownCollection()` or
   `loadYamlCollection()`, plus a cache key in `cacheKeys()`.
3. Collection path in `config/electroserves.php` and a matching Decap collection.
4. Tests for the missing / malformed / incomplete cases.

**Adding a page**
Controller returns a view with `seo` (from `SeoService::forPage()`), `schema`,
and `breadcrumbs`. Every collection rendered needs an empty state.

**Dates in YAML** — `date: 2026-01-12` parses to an **integer timestamp**, not a
string. Always read dates through the `ParsesDates` trait, which handles
strings, timestamps and `DateTimeInterface`.

---

## Test coverage — 153 tests, 354 assertions

| File | Covers |
|---|---|
| `PagesTest` | Every route at 200 **with and without content**; 404s for unknown slugs; traversal rejection; SEO tags; skip links; structured data |
| `ContentServiceTest` | Missing / malformed / incomplete content; ordering; filtering; relationships; caching; path safety |
| `ContactFormTest` | Full validation cycle; honeypot; timing check; rate limit; transport failure; missing recipient |
| `SecurityHeadersTest` | All headers on pages, errors and redirects; HSTS only over HTTPS |
| `EnvironmentValidationTest` | Missing and blank variables; SMTP-conditional requirements |
| `DecapCmsConfigTest` | Config parses; collections match `config/electroserves.php`; folders exist |
| `Unit/DataObjects/*` | Required-field rejection; defaults; type coercion |
| `Unit/Services/*` | Markdown sanitisation (XSS, iframes, `javascript:`); YAML edge cases (CRLF, BOM, non-mappings) |

---

## Two bugs found during Phase 1

1. **Invalid YAML in the Phase 0 CMS config** — `Business Hours` mixed a flow
   mapping with a block sequence. Would have rendered a blank `/admin` with no
   server-side error. Fixed in `public/admin/config.yml` and the source
   document; locked in by `DecapCmsConfigTest`.
2. **Unquoted YAML dates dropped** — Symfony resolves them to integers, which
   the string accessor discarded, so blog posts sorted wrongly. Caught by an
   ordering test; fixed with the `ParsesDates` trait.

---

## Outstanding

- **`.github/ci/ci.yml` must be moved to `.github/workflows/ci.yml`** by an
  account with the GitHub `workflows` permission. The file needs no edits.
- **`composer.lock` is gitignored** — Packagist is unreachable from the build
  sandbox, so the lock generated there recorded local artifact paths. Run
  `composer update` where Packagist is reachable and commit the result.

## Deferred by design

Response caching, `deploy/nginx.conf`, and the deployment guide are Phase 4.
Pagination renders but is not yet wired to the project and blog indexes
(Phase 3).
