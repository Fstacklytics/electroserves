# Phase 4 — Gap Matrix (audit before implementation)

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


Audit performed against the repository as it stood at the end of Phase 3
(369 tests / 1,297 assertions, production CSS+JS 148,144 bytes pre-gzip).

Status vocabulary:

- **Satisfied** — already implemented in Phase 1–3 and verified by reading the source; no Phase 4 work needed.
- **Partial** — present but incomplete or unverified; Phase 4 must extend it.
- **Missing** — not implemented; Phase 4 must build it.
- **N/A** — out of scope for a public, file-backed Laravel site (Phase 0 scope boundary), with reason.

---

## 4.1 — Performance Optimization

| # | Requirement | Status | Evidence / action |
|---|---|---|---|
| 1.1 | Laravel response caching, configurable per-page TTL | **Resolved** | `app/Services/ResponseCacheService.php` + `app/Http/Middleware/ResponseCacheMiddleware.php`, driven by a `response_cache` block in `config/electroserves.php`. Per-route TTLs: home 600s, services/projects 1800s, blog 600s, static 3600s, legal 86400s, sitemap 3600s, default 600s. Built on Laravel's own `Cache` facade — no `spatie/laravel-responsecache`. Responses carry `X-Response-Cache: HIT\|MISS\|BYPASS`. |
| 1.2 | GET/HEAD only, never POST | **Resolved** | The middleware serves only GET and HEAD; HEAD reuses the GET entry. Asserted by `ResponseCacheTest`. |
| 1.3 | No caching of redirects, errors, validation responses, session-dependent or flash-bearing responses | **Resolved** | All excluded and tested. **Root cause found while building this:** every `web` response carries Symfony's synthesised `Cache-Control: no-cache, private` plus `XSRF-TOKEN`/`electroserves_session` cookies, which would have disabled caching entirely. The service skips exactly that default string (const `SYMFONY_DEFAULT_CACHE_CONTROL`) and exactly those two cookies, while still refusing any other `no-store`/`private` or any additional cookie. Reconciled with `SecurityHeadersMiddleware` rather than contradicting it — see the new defect note below. |
| 1.4 | Contact page + submission treated as sensitive (never cached) | **Resolved** | Routes `contact`, `contact.store` and `styleguide` are excluded by name; paths `admin`, `admin/*` and `up` by path. A CSRF-token backstop refuses to cache any response containing one, so a future route cannot be cached by accident. Asserted by `ResponseCacheTest` and by `FormAccessibilityTest::test_the_error_response_is_never_cached`. |
| 1.5 | Invalidation when file-backed content changes | **Resolved** | The cache key embeds a content fingerprint, so editing any file in `content/` changes every key and stale entries become unreachable — no explicit purge step to forget. `content:flush` and a new `responsecache:clear` command are wired in `routes/console.php`. |
| 1.6 | Query-string separation (`?page=`, `?category=`) | **Resolved** | The key includes the normalised, sorted query string, so `?page=2` and `?category=x` are separate entries. Tracking parameters (5 `utm_*`, `gclid`, `fbclid`, `ref`) are stripped so they cannot fragment the cache — but `page`, `category` and `service` are deliberately **not** stripped. |
| 1.7 | Canonical URLs preserved | Satisfied | `SeoService::forPage()` sets `canonical` including `?page`. Response cache must not alter it — covered by keying on the full query. |
| 1.8 | Avoid heavy caching dependencies | Satisfied (by decision) | Implemented on Laravel's own `Cache` facade; no `spatie/laravel-responsecache`. |
| 1.9 | Tests: hits, TTL, exclusions, invalidation, query separation, unsafe responses | **Resolved** | `tests/Feature/ResponseCacheTest.php` — 28 tests covering all six areas. Console coverage uses `Artisan::call(...)` with a `BufferedOutput` because `$this->artisan()` hard-crashes the php-wasm runtime used in this sandbox (see the PR notes). |
| 1.10 | All `<img>` have width/height | Satisfied | Only three `<img>` tags exist: two in `components/ui/media.blade.php`, one in `components/common/navbar.blade.php`. All carry explicit `width`/`height`. |
| 1.11 | `loading="lazy"` for non-hero, eager for hero/LCP | Satisfied | `x-ui.media` emits `loading="{{ $eager ? 'eager' : 'lazy' }}"` and `fetchpriority="high"` when eager; `sections/hero.blade.php` passes `:eager="$index === 0"`. Navbar logo is `loading="eager"` (correct, it is above the fold). |
| 1.12 | `decoding="async"` | Satisfied | Present on all three `<img>` tags. |
| 1.13 | Meaningful vs empty alt | Satisfied | Hero backgrounds pass `alt=""` (decorative, heading carries meaning); placeholder branch uses `role="img" aria-label` when `label` is given and `aria-hidden="true"` otherwise. |
| 1.14 | Local placeholders, no external placeholder service | Satisfied | `.image-placeholder` gradient + inline SVG bolt mark in `x-ui.media`. Grep for `placehold.co`/`via.placeholder`/`unsplash`/`dummyimage` across `content/ app/ resources/ config/ routes/` returns nothing. |
| 1.15 | Preconnect only for domains actually used | **Resolved — verified, no change needed** | `layouts/app.blade.php` preconnects `fonts.googleapis.com` + `fonts.gstatic.com`, both genuinely used by the Google Fonts stylesheet. No analytics preconnect (none used). Verified correct; documented, no change. |
| 1.16 | `font-display: swap` | Satisfied | The Google Fonts URL carries `&display=swap`, and the `<link>` uses the `media="print" onload="this.media='all'"` async pattern with a `<noscript>` fallback. |
| 1.17 | Critical CSS, maintainable, CSP-compatible | **Resolved — deliberately not inlined** | Measured, then rejected. The whole production stylesheet is **64,591 B raw / 10,458 B gzipped**, served same-origin from `/build/` with `immutable` caching. Inlining an above-the-fold subset only helps when the stylesheet costs a *separate, slow* round trip; here it is one HTTP/2 request on the same connection, cached forever after the first visit. A hand-maintained block would duplicate rules that already exist in `app.css` (`[x-cloak]`, base typography, focus ring), drift from them silently, and add bytes to **every** HTML response — including cached ones, where the stylesheet costs nothing. A build-time extractor was rejected under the "no heavy dependencies" rule. The one rule that genuinely must apply before `app.css` parses — hiding `[data-js-only]` controls for non-JS visitors — *is* inlined, in the `<noscript>` block in `layouts/app.blade.php`, and is CSP-legal under `style-src 'self' 'unsafe-inline'`. Revisit only if a real-user LCP measurement shows the stylesheet on the critical path. |
| 1.18 | Minified assets | Satisfied | Vite production build minifies by default; `build.sourcemap: false`. |
| 1.19 | No unnecessary dependencies | Satisfied | Runtime deps are `alpinejs`, `@alpinejs/focus`, `@alpinejs/collapse` only. No jQuery, no framework. |
| 1.20 | CSS+JS < 500 KB pre-gzip, before/after recorded | **Resolved — measured** | Before (end of Phase 3): **148,144 B**. After Phase 4: **148,989 B** pre-gzip (`app-Bc25kn5q.js` 84,398 B raw / 28,594 B gzip; `app-DEG3TnDB.css` 64,591 B raw / 10,516 B gzip). Delta **+845 B** (+0.57%), from the clipboard fallback and the no-JS `[data-js-only]` rules. **29.8% of the 500 KB budget.** |
| 1.21 | gzip/Brotli compression | **Resolved** | `deploy/nginx.conf` enables `gzip` for text/CSS/JS/SVG/JSON with `gzip_vary on`; Brotli is provided as a commented block with the `libnginx-mod-http-brotli-*` install line, because it needs a module that is not present on a stock Nginx. Asserted by `DeploymentConfigTest`. |

## 4.2 — Accessibility

| # | Requirement | Status | Evidence / action |
|---|---|---|---|
| 2.1 | Skip-to-content link | Satisfied | `components/common/skip-to-content.blade.php`, `.skip-link` becomes visible on `:focus`; `<main id="main-content" tabindex="-1">`. Asserted by `AccessibilityTest`. |
| 2.2 | Visible focus on every interactive element | Satisfied | Global `:focus-visible { ring-2 ring-primary-600 ring-offset-2 }` in `resources/css/app.css` base layer; no `outline: none` without replacement. |
| 2.3 | One `<h1>` per page, landmarks | Satisfied | Asserted by `AccessibilityTest::test_every_page_has_exactly_one_h1` and `..._required_landmarks` across 13 page URIs. The hero renders `<h1>` only for slide 0 and `<p>` for the rest — correct. |
| 2.4 | Form labels, descriptions, error association, validation summary | **Resolved** | Audited and covered end-to-end by the new `tests/Feature/FormAccessibilityTest.php` (12 tests): a real rejected POST is followed through the redirect, then every failing field is asserted to carry `aria-invalid="true"` and an `aria-describedby` that resolves to an element actually present in the document; fields that passed are asserted **not** to be flagged; the summary is `role="alert"`, `tabindex="-1"`, and lists one self-describing message per failing field. |
| 2.5 | `aria-live` / `role="status"` regions | **Resolved** | Covered by `AccessibilityTest::test_dynamic_result_counts_are_announced`, `ComponentsTest::test_toast_container_is_a_polite_live_region`, and `FormAccessibilityTest::test_the_character_counter_is_a_polite_live_region`, which also asserts nothing on the contact form uses `aria-live="assertive"` (the summary uses `role="alert"` instead, so the counter cannot interrupt on every keystroke). |
| 2.6 | `aria-expanded` / `aria-controls` | Satisfied | `dropdown.js` `triggerAttrs()` binds `:aria-expanded`; navbar hamburger controls `#mobile-menu`. |
| 2.7 | `aria-current` | Satisfied | Navbar + mobile menu set `aria-current="page"` on the active link; pagination sets it on the current page; carousel dots bind `:aria-current`. |
| 2.8 | `aria-disabled` | Satisfied | Pagination previous/next render `<span aria-disabled="true">` instead of a disabled link. |
| 2.9 | 44×44 px targets | Satisfied | `.touch-target` (`min-h-touch min-w-touch`) and `min-h-touch` used on nav links, pagination, buttons; `touch` spacing token defined in `tailwind.config.js`. |
| 2.10 | WCAG AA contrast | **Resolved** | `tests/Unit/ColourContrastTest.php` (24 tests / 105 assertions) computes WCAG 2.1 relative-luminance ratios arithmetically for every token pair the templates actually use. Measured: primary-700/white 6.70, primary-800/white 8.72, neutral-700/white 10.35, neutral-500/white 4.76, secondary-700/white 5.02, neutral-900/secondary-400 10.69. **Finding:** neutral-500 on neutral-100 is **4.34 — below AA**; the test pins muted text to a white background so that pairing cannot be introduced. |
| 2.11 | Colour not the sole indicator | Satisfied | Active nav uses background + `aria-current`; filter tabs use `aria-selected`; form errors use text + icon + `aria-describedby`. |
| 2.12 | Dialog / lightbox focus trap, initial focus, Escape, body scroll, focus restoration | Satisfied | `lightbox.js` stores `previouslyFocused`, locks `document.body.style.overflow`, restores focus on close; `modal.js` and the mobile menu use `x-trap.noscroll`. Verified by reading source. |
| 2.13 | Mobile nav focus handling | Satisfied | `x-trap.noscroll="mobileOpen"`, Escape on navbar root, links close the panel so focus is never left inside a hidden element. |
| 2.14 | `prefers-reduced-motion` | Satisfied | CSS media query neutralises animation/transition/scroll-behaviour; `Alpine.store('motion')` mirrors it in JS with a Safari < 14 `addListener` fallback; the carousel consults it. |
| 2.15 | Image alternatives | Satisfied | See 1.13. |
| 2.16 | No duplicate IDs | **Resolved** | `AccessibilityTest::test_no_page_contains_duplicate_element_ids` parses every page URI and fails on a repeated `id`. Component ids are randomised per instance (e.g. `textarea-message-p4Vq`), so repeated components cannot collide. |
| 2.17 | Keyboard operation (Tab/Shift+Tab/Enter/Space/Escape/Arrows) | **Partial (source audit only)** | `dropdown.js`, `tabs.js`, `accordion.js`, `carousel.js`, `lightbox.js` all implement documented keyboard contracts. A real keyboard pass in a browser is **not** possible in this environment and will not be claimed. |
| 2.18 | Screen-reader verification | **N/A in this environment** | No screen reader available in the sandbox. Recorded as a deployment-verification step, never as a passing check. |

## 4.3 — Browser Compatibility / Progressive Enhancement

| # | Requirement | Status | Evidence / action |
|---|---|---|---|
| 3.1 | Chrome 90+, Firefox 90+, Safari 14+, Edge 90+ | **Resolved** | `vite.config.js` now pins `build.target: ['chrome90', 'firefox90', 'safari14', 'edge90']`, so the support floor is intentional rather than inherited from a Vite default that can change between majors. Kept in sync with the `browserslist` in `package.json` (see 3.7). |
| 3.2 | Clipboard API secure-context fallback | Satisfied | `copy-link.js` throws when `navigator.clipboard?.writeText` is absent and reports failure to the user rather than silently appearing to succeed. |
| 3.3 | URL / History API | Satisfied | `tabs.js` uses `new URL()` + `history.replaceState`, both available in all four target browsers. |
| 3.4 | Focus trap / lightbox | Satisfied | Uses `@alpinejs/focus`, which supports the target range. |
| 3.5 | Alpine directives | Satisfied | Alpine 3 supports the stated browsers. |
| 3.6 | Media queries / reduced motion | Satisfied | `addEventListener`-with-`addListener`-fallback in `app.js` covers Safari < 14. |
| 3.7 | CSS fallbacks / prefixes | **Resolved** | `package.json` now declares an explicit `browserslist` (`Chrome >= 90`, `Firefox >= 90`, `Safari >= 14`, `Edge >= 90`) so Autoprefixer targets the documented matrix instead of its own default query. **These two lists must be kept in sync with `vite.config.js` `build.target`.** `text-wrap: balance` and `aspect-ratio` degrade gracefully; `-webkit-text-size-adjust` already present. |
| 3.8 | Works without JS: content visible | Satisfied | All pages render server-side; Blade emits full markup. |
| 3.9 | Works without JS: link navigation | Satisfied | Navbar/footer/breadcrumb/pagination are plain `<a href>`. |
| 3.10 | Works without JS: normal form submit | Satisfied | Contact form is a real `<form method="POST">`; `contact-form.js` only enhances. |
| 3.11 | Works without JS: pagination | Satisfied | Server-side `LengthAwarePaginator` links. |
| 3.12 | Filtering as progressive enhancement only | **Resolved** | `pages/projects/index.blade.php` wraps each card in `<li x-show="matches(...)">`. With Alpine present but before init, `x-show` has not run, so items are visible — fine. **But** the "0 results" empty state uses `x-cloak`, and the filter tab bar is rendered even without JS, where clicking a tab does nothing. Without JS the tabs were dead controls. **Fixed:** the JS-only filter UI is marked `data-js-only` and hidden by a `[data-js-only]{display:none !important}` rule inlined inside the existing `<noscript>` block, so non-JS visitors are never shown a control that does nothing. Server-side filtering was deliberately **not** added: `lang/en/projects.php` `filter_scope` documents that filtering is scoped to the current page only. Covered by `ProgressiveEnhancementTest` (17 tests). |
| 3.13 | Share links usable without JS | Satisfied | Twitter/Facebook/LinkedIn share links are plain `<a href>`; only the copy-link button needs JS, and it is an addition, not the only path. |

## 4.4 — Nginx Configuration

| # | Requirement | Status |
|---|---|---|
| 4.1–4.16 | `deploy/nginx.conf` (HTTP→HTTPS, TLS 1.2/1.3, docroot, front controller, PHP-FPM params, deny hidden/sensitive files, no autoindex, body limits, headers compatible with app middleware, gzip + Brotli guidance, immutable hashed-asset caching, no caching of contact submissions, contact rate limiting matching the Laravel limiter, logging, timeouts, no wildcard CORS, operator comments) | **Resolved** — `deploy/nginx.conf` written, making good on the promise already in `SecurityHeadersMiddleware`'s docblock. Two deliberate **non**-duplications: (1) security headers are set **only** by the middleware, because a single `add_header` inside a `location` silently drops every inherited header; (2) **no `fastcgi_cache`**, because page caching belongs in `ResponseCacheService`, which knows which routes carry CSRF and session state — Nginx does not. Rate-limit zones are deliberately **looser** than the app limiter (30r/m and 120r/m vs 5/hour per IP) so Laravel returns its own translated 429 rather than a bare Nginx error page. |
| 4.17 | Automated assertions where practical | **Resolved** — `tests/Feature/DeploymentConfigTest.php`, 27 tests / 117 assertions, parses `deploy/nginx.conf` and asserts each decision below. Note `nginx -t` **could not be run** (nginx is not installed in this sandbox), so these are config assertions, not a syntax validation. |

## 4.5 — Deployment Documentation

| # | Requirement | Status |
|---|---|---|
| 5.1–5.20 | `deploy/README.md` (versions, PHP extensions, server prep, release-directory deploy, composer/npm install, asset build, env reference, APP_KEY/mail/URL/cache/session/logging/CMS config, ownership/permissions, artisan cache commands, response-cache warm/clear, Nginx install/validate, Let's Encrypt + renewal, Decap GitHub OAuth, backup/restore, health/smoke checks, rollback, post-deploy validation, monitoring/incident response, failure recovery, security/dependency audit, credential-free checklist) | **Resolved** — `deploy/README.md` written, 16 sections, keyed to this repository's real paths, scripts and config names (no invented commands: `php artisan test` is not a registered command here, so the README uses `vendor/bin/phpunit`). Env reference is derived from `required_env`/`required_env_smtp` and contains no secrets. |

## 4.6 — Content

| # | Requirement | Status | Evidence |
|---|---|---|---|
| 6.1 | Minimum counts | Satisfied | 6 services, 6 projects, 4 blog posts, 6 testimonials, 5 team, 10 FAQs, 3 hero slides, `settings/{site,seo}.yml`, `pages/{about,privacy-policy,terms}.md`. All meet or exceed the minimum. |
| 6.2 | No Lorem Ipsum / TODO / dev fixtures | Satisfied | Grep across `content/` returns no matches. |
| 6.3 | No external placeholder images | Satisfied | Grep returns no matches; `public/uploads` does not exist, so every image reference falls through to the local gradient placeholder. |
| 6.4 | Realistic and Tanzania-appropriate | **Resolved — verified** | Read through services, projects, testimonials, team and hero content. Prose is specific and professional (named districts, real service categories, plausible client sectors). Contact details are consistent everywhere: `hello@electroserves.co.tz` (x4), `+255 754 900 111`, `+255 22 213 4567`, plus per-person `joseph@`/`amina@` addresses. All four blog dates (2025-10-05, 2025-11-28, 2026-01-12, 2026-03-18) are in the past. Pinned by `ContentIntegrityTest` (contact-detail consistency, `+255` prefix, future-date guard). |
| 6.5 | Complete valid frontmatter | **Resolved — verified** | Every entry parses and every required field is present. Pinned by `ContentIntegrityTest`, which asserts per-collection required fields against the **real** `content/` directory (not fixtures), plus slug URL-safety and slug uniqueness per collection. |
| 6.6 | Graceful handling of missing optional fields | **Resolved — verified** | Optional image fields are empty strings on several entries and fall through to the local gradient placeholder in `components/ui/media.blade.php` (covered by `ComponentsTest::test_media_renders_a_placeholder_when_the_image_is_missing`). This matters because the CSP is `img-src 'self' data:`, so a remote image would be blocked outright — `ContentIntegrityTest` bans both external placeholder services and any remote image URL in `content/`. |
| 6.7 | Graceful page (not 500) on content failure | Satisfied | `ContentService` never throws to the controller; malformed entries are skipped and logged. Covered by `ContentServiceTest`. |

## Cross-cutting tooling gaps found during the audit

| # | Item | Status |
|---|---|---|
| T1 | `npm run lint:js` | **Resolved** — `.eslintrc.cjs` added (ESLint 8.57 eslintrc format, browser + ES2022 env, Alpine as a global). `npm run lint:js` now exits 0. |
| T2 | `npm run lint:css` | **Resolved** — `.stylelintrc.json` added, extending `stylelint-config-standard` with Tailwind at-rules allowed. Running it surfaced **two real CSS defects**, which were fixed rather than suppressed. `npm run lint:css` now exits 0. |
| T3 | `.github/ci/ci.yml` | **Misplaced** — belongs at `.github/workflows/ci.yml`; move only if this session has workflow-write permission, otherwise re-flag in the PR. |
| T4 | `composer.lock` | **Absent, must stay absent** — Packagist is unreachable from this sandbox, so a lock file cannot be generated legitimately. Must not be hand-written. |

---

## Defects found and fixed during Phase 4

These were not on the checklist. They were found by writing tests that exercised
real behaviour rather than asserting on source strings.

### D1 — `Cache-Control: no-store` on validation errors was dead code

`SecurityHeadersMiddleware` contained:

```php
if (... && $request->session()->has('errors')) {
    $response->headers->set('Cache-Control', 'no-store, private');
}
```

This condition was **never true**, so the header was never sent. The middleware is
registered globally, so it unwinds *outside* the `web` group: by the time it runs,
`StartSession` has already saved the session, and `Store::save()` calls
`ageFlashData()`, which forgets the `errors` key. A page rendering another
visitor's validation errors — and carrying a CSRF token — was therefore being
served with Symfony's default `no-cache, private` instead of `no-store`.

`no-cache` permits a shared cache to *store* the response and revalidate;
`no-store` forbids storing it at all. On a site behind a CDN or corporate proxy
that is the difference between "revalidated" and "never written to disk".

**Fix:** read the error bag that `ShareErrorsFromSession` shares with the view
factory, which nothing ages, instead of the session. Regression test:
`FormAccessibilityTest::test_the_error_response_is_never_cached`.

This is also why the response cache was built to inspect the session from
*inside* the `web` group — same hazard, avoided by placement.

### D2 — two real CSS defects

Surfaced by adding the missing `.stylelintrc.json` (tooling gap T2). Fixed at
source rather than suppressed with disable comments.

### D3 — a dead no-JS control on the blog page

The share/copy button rendered for visitors without JavaScript but did nothing.
Found by `ProgressiveEnhancementTest`. Fixed, and the general rule is now
enforced: every server-sent control either works without JS or is marked
`data-js-only` and hidden.

---

## Validation results

Measured on the sandbox runtime. **PHP 8.5.8** — the documented baseline is PHP
8.3, and no 8.3 runtime could be installed here (no system PHP, and Debian/Sury
mirrors are unreachable), so this is a runtime drift to be aware of when reading
these numbers. `composer.json` still requires `^8.3`.

| Check | Result |
|---|---|
| Full PHPUnit suite | **498 tests / 2,786 assertions, 0 failures**, 2 pre-existing deprecations (Phase 3 baseline: 369 / 1,297) |
| `npm run lint:js` | Passes (was broken — no config existed) |
| `npm run lint:css` | Passes (was broken — no config existed) |
| `npm run build` | Succeeds |
| Production CSS+JS | **148,989 B** pre-gzip vs 500 KB budget (**29.8%**); Phase 3 was 148,144 B, so **+845 B** |
| `git diff --check` | Clean |
| Content minimums | 6 services / 6 projects / 4 posts / 6 testimonials / 5 team / 10 FAQs / 3 hero slides — all met |

### Checks that could NOT be run

Stated precisely, not reported as passing:

- **`nginx -t`** — nginx is not installed in this sandbox. `deploy/nginx.conf` is
  covered by 27 config assertions, but its syntax has **not** been validated by
  nginx itself. `deploy/README.md` §7 requires `sudo nginx -t` before activation.
- **`composer audit` / `composer install`** — Packagist is unreachable from this
  sandbox. Dependencies were resolved by a local shim; `composer.lock` is
  deliberately **absent** and must be generated where Packagist is reachable.
- **`php artisan test`** — not a registered command in this repository; the suite
  runs via `vendor/bin/phpunit`.
- **Real browser and screen-reader testing** — no browser in this environment. All
  accessibility and browser-compatibility work here is automated assertion or
  source audit. Nothing in this phase claims manual verification.
