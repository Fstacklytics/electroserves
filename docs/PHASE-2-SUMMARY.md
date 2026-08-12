# Phase 2 — Design System: what was built

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


Builds on `docs/PHASE-1-SUMMARY.md`. Conventions there still apply.

---

## Component inventory

Everything below lives under `resources/views/components/` and is exercised by
`/styleguide` in every state.

### `ui/` — primitives

| Component | States covered |
|---|---|
| `button` | 6 variants × 3 sizes · hover · focus · active · disabled · loading · renders `<a>` or `<button>` |
| `input` · `textarea` · `select` · `checkbox` | default · focus · error · disabled · readonly · required · with help text |
| `alert` | success · info · warning · error · dismissible |
| `card` | bordered · hoverable · header/body/footer slots · padding scale |
| `badge` | 6 variants × 2 sizes |
| `spinner` | 3 sizes · announced or decorative |
| `skeleton` | single and multi-line |
| `empty-state` | with and without a title, icon and recovery action |
| `star-rating` | 0–5, clamped, announced as text |
| `media` | real image · WebP `<picture>` · missing-file placeholder · unsafe reference rejected |
| `modal` | closed · open · with/without title and footer |
| `dropdown` | closed · open · keyboard navigation |
| `accordion` | single/multiple · initial open · **empty state** |
| `tabs` | roving tabindex · manual activation |

### `common/` — chrome

`navbar` (desktop + mobile), `mobile-menu`, `footer`, `breadcrumb`,
`pagination-links`, `seo-head`, `favicon`, `skip-to-content`, `toast-container`,
`cookie-banner`.

### `sections/` — page furniture

`hero`, `services-grid`, `featured-projects`, `testimonials-carousel`, `stats`,
`cta-banner`, `page-header`, `section-heading`, `error-panel`, and the three
card components (`service-card`, `project-card`, `blog-card`, `testimonial-card`).

---

## Accessibility guarantees, now enforced by tests

`tests/Feature/AccessibilityTest.php` runs these against **every** public page:

- exactly one `<h1>`, and a declared `lang`
- `header` / `main` / `footer` landmarks, with a working skip link
- **no duplicate element ids**
- every `<img>` has `alt` plus explicit `width`/`height` (CLS protection)
- every inline `<svg>` is either `aria-hidden` or labelled
- every `target="_blank"` link carries `rel="noopener noreferrer"`
- every contact form control resolves to a `<label for>`
- the carousel exposes its role and a pause control (WCAG 2.2.2)
- filtered result counts sit in an `aria-live` region

`tests/Feature/ComponentsTest.php` renders each component in isolation and
asserts on props, states and ARIA wiring — 82 tests covering the library.

---

## Notable decisions

**Alpine state lives with the trigger.** `mobile-menu` and `modal` are rendered
*inside* the caller's `x-data` scope rather than owning their own, because the
control that opens them is a sibling. Extracting the panel without extracting
the state would have broken the binding.

**The cookie banner is a notice, not a consent gate.** Per
`docs/phase-0/05-pii-classification.md` the site sets no tracking cookies at
launch — only the session cookie CSRF requires, which is strictly necessary.
Dismissal is stored in `localStorage`, so acknowledging the notice does not
itself create the thing it describes. If analytics are added, this component
becomes the gate and needs explicit accept/reject controls before any script
loads.

**Accordion content typing.** `x-ui.accordion` escapes plain strings and renders
`HtmlString` as HTML, so the FAQ page can pass controller-sanitised Markdown
while a careless caller cannot introduce an XSS sink.

---

## Bug found and fixed

**Form controls assumed `$errors` always exists.** It is only shared by the
session middleware, so `<x-ui.input>` threw `Undefined variable $errors` when
rendered outside a request — in a test, a mail view, or any future queued
render. All four form components now resolve the bag defensively. Caught by the
new component tests.

---

## Refactors

- The mobile panel moved out of `navbar` into `common/mobile-menu`.
- The FAQ page's hand-rolled accordion markup was replaced by `x-ui.accordion`,
  removing ~40 lines of duplicated ARIA wiring.
- The contact form's hand-rolled consent checkbox now uses `x-ui.checkbox`.
- `skip-to-content` extracted from both layouts into one component.

---

## Test counts

| Phase | Tests | Assertions |
|---|---|---|
| After Phase 1 | 153 | 354 |
| After Phase 2 | **359** | **1251** |

Run with `php vendor/bin/phpunit`.

---

## Still outstanding from Phase 1

- **`.github/ci/ci.yml` needs moving to `.github/workflows/ci.yml`** by an
  account holding the GitHub `workflows` permission.
- **`composer.lock` is gitignored** — regenerate with `composer update` where
  Packagist is reachable.
