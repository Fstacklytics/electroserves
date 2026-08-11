# Next session — Phase 4 prompt

Copy everything below the line into a new Arena chat to continue with Phase 4.

---

Continue the **ElectroServes** build in `Fstacklytics/electroserves`. **Phases 0–3 are complete.** Read the existing implementation before changing it, then execute only Phase 4, **“Polish & Hardening,”** from `docs/IMPLEMENTATION-PROMPT.md`.

## First, read these

1. `docs/IMPLEMENTATION-PROMPT.md`, especially sections 4.1–4.6.
2. `docs/phase-0/*` for the architecture, scope boundary, threat model, SLOs, and CMS decisions.
3. `docs/PHASE-1-SUMMARY.md`, `docs/PHASE-2-SUMMARY.md`, and the Phase 3 notes below.
4. The current middleware, layouts, image component/service, Alpine components, CSS, content, tests, and any deployment files before proposing replacements.

## Phase 3 completion state

Phase 3 was completed by extending the existing pages rather than rebuilding them:

- The Phase 3 checklist was audited across the home, services, projects, blog, about, contact, FAQ, testimonials, legal, and custom error surfaces.
- `/projects` now uses a manual `LengthAwarePaginator` at `config('electroserves.pagination.projects')` (12 items per page).
- `/blog` now uses a manual `LengthAwarePaginator` at `config('electroserves.pagination.blog')` (6 items per page).
- Positive pages beyond the last page return the custom 404 instead of duplicating page 1 or rendering a misleading empty index.
- Pagination links retain recognized category queries, and Alpine updates those links when visitors change filters.
- **Deliberate filter decision:** project and blog category filtering applies only to the server-rendered current page. The interface states this explicitly; filtering is progressive enhancement, so all current-page cards remain available without JavaScript.
- Page 2 has its own canonical URL, while category-only views canonicalize to the underlying index page.
- Blog posts retain generated, uniquely anchored h2/h3 tables of contents plus X, Facebook, LinkedIn, and Clipboard API sharing states.
- Project lightboxes retain Escape, ArrowLeft/ArrowRight, focus trapping/restoration, body-scroll locking, and wrapping navigation.
- Service-detail quote links continue to send `?service=<service-slug>`; the contact controller now resolves a recognized slug to its configured service category. Direct category queries remain supported and unknown values leave the dropdown blank.
- `PagesTest` and `ContactFormTest` cover page 2, configured page sizes, canonical URLs, out-of-range pages, category-query persistence, TOC/share markup, lightbox keyboard bindings, quote-link slug flow, and safe unknown service queries.

Validated at the end of Phase 3:

- `369` PHPUnit tests passed with `1,297` assertions on PHP 8.3.
- PHP syntax checks passed.
- Blade compilation passed.
- `npm run build` passed.
- The production CSS + JS bundle was `148,144` bytes, below the 500 KB budget.
- `git diff --check` passed.

## Scope — Phase 4 only

Work through every Phase 4 acceptance point rather than assuming it is absent. Several foundations already exist and should be verified and improved, not duplicated:

### 4.1 Performance optimization

- Add configurable per-page response caching with safe exclusions/invalidation. Inspect `SecurityHeadersMiddleware` first: it currently sets restrictive cache headers, so response caching and privacy must be reconciled deliberately.
- Audit all images for explicit dimensions, lazy/eager loading, and async decoding. `x-ui.media` already centralizes most of this behavior; the navbar logo is a separate `<img>`.
- Verify preconnects, font loading, critical CSS strategy, bundle composition, and production compression. Preconnect hints and reduced-motion support already exist.
- Measure before and after; do not add a heavy caching or analysis package without demonstrating the need.

### 4.2 Accessibility audit

- Perform keyboard-only checks across every page and interactive component.
- Verify focus visibility, ARIA, contrast, target sizes, skip link, modal/lightbox focus trapping and restoration, and reduced-motion behavior.
- Existing `AccessibilityTest`, component tests, `x-trap`, and `prefers-reduced-motion` rules are a baseline, not a substitute for the requested audit.
- Fix regressions and add targeted assertions rather than duplicating broad existing coverage.

### 4.3 Browser compatibility

- Verify Chrome 90+, Firefox 90+, Safari 14+, and Edge 90+ compatibility.
- Preserve progressive enhancement and provide fallbacks for unsupported browser APIs or CSS.
- Pay particular attention to Clipboard API sharing, dialog-like interactions, media queries, and generated production assets.

### 4.4 Nginx configuration

Create `deploy/nginx.conf` with the full brief: modern TLS, security headers, gzip, immutable caching for hashed assets, PHP-FPM routing, disabled directory listing, and contact-endpoint rate limiting. Keep application and proxy security headers compatible rather than contradictory.

### 4.5 Deployment documentation

Create `deploy/README.md` covering requirements, deployment steps, environment variables, Let’s Encrypt, Decap CMS GitHub OAuth, backups, permissions, cache warming/clearing, rollback, and validation. Commands must match this repository rather than a generic Laravel template.

### 4.6 Content population

Audit the existing realistic sample content against every minimum in the brief before adding anything. At the Phase 3 handoff there were 6 services, 6 projects, 4 blog posts, 6 testimonials, 5 team members, 10 FAQs, and 3 hero slides. Preserve graceful placeholders and do not introduce external placeholder-image dependencies.

## Validation and delivery

- Keep all existing functionality, empty/error/loading states, SEO, validation boundaries, accessibility, and responsive behavior intact.
- Run the complete PHPUnit suite, PHP syntax checks, Blade compilation, the production frontend build, asset-budget check, and all applicable repository audits.
- Update or add tests for Phase 4 behavior, especially response-cache safety and deployment configuration.
- Do not begin an unrequested Phase 5.
- Rewrite this file with the final project handoff when Phase 4 is complete.
- Commit and push only the Arena-bound branch for that session, then open the Phase 4 PR against `main`.

## Environment and carried-over repository issues

A normal environment needs PHP 8.3, Composer 2, Node 20, an application key, Composer dependencies, and frontend dependencies. Do not assume ignored local `vendor/`, `node_modules/`, `.env`, or toolchain caches are part of the clone.

Two issues still require privileged or network-capable human follow-up and must be re-flagged in the Phase 4 PR if unresolved:

1. **Move `.github/ci/ci.yml` to `.github/workflows/ci.yml`.** This needs a GitHub identity/token with workflow-writing permission; until moved, GitHub Actions will not discover it.
2. **Regenerate and commit `composer.lock` where Packagist is reachable.** Packagist was unreachable in the Phase 3 sandbox, so its local GitHub-sourced compatibility lock was only an ignored installation aid and was not delivered.
