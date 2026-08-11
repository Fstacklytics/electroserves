# Next session — Phase 2 prompt

Copy everything below the line into a new Arena chat to continue with Phase 2.

---

Continue the **ElectroServes** build in `Fstacklytics/electroserves`. **Phase 1 is complete and merged/open as PR #1** — read it before starting so you build on what exists rather than duplicating it.

## First, read these

1. `docs/phase-0/*` — all ten decision documents (data model, threat model, scope boundary, ADRs, SLOs, CMS config).
2. `docs/IMPLEMENTATION-PROMPT.md` — the full brief; **Phase 2 is section "Phase 2: Design System"**.
3. `docs/PHASE-1-SUMMARY.md` — what Phase 1 delivered and the conventions to follow.
4. The existing code, especially `app/Services/ContentService.php`, `app/DataObjects/`, `resources/views/components/`, and `tests/`.

## Environment note — read this before running anything

This sandbox **cannot reach Packagist, deb.debian.org, or packages.sury.org**, so there is no system PHP and `composer install` cannot use the default repository. Phase 1 set up a working toolchain; reuse it rather than rebuilding it:

```bash
cd ~/electroserves
. ./.sandbox-toolchain.sh     # puts php + composer on PATH
php vendor/bin/phpunit        # 153 tests should pass
npm run build                 # should build clean
```

If `vendor/` is missing, the artifact set is at `/home/user/php-artifacts/artifacts` and the fetcher is `/home/user/.phpwasm/fetch-artifacts.mjs`. `php artisan serve` does **not** work (the WASM runtime cannot bind sockets) — render pages through the HTTP kernel to a directory and serve that statically, as Phase 1 did.

## Scope for this session — Phase 2 only

Work through the Phase 2 checklist in the implementation prompt. Much of the design system already exists from Phase 1; **audit what is there first**, then complete the gaps:

**Already built** (verify and extend, do not rewrite): `button`, `card`, `badge`, `input`, `textarea`, `select`, `alert`, `skeleton`, `spinner`, `media`, `star-rating`, `empty-state`; `app.blade.php`, `error.blade.php`, `navbar`, `footer`, `breadcrumb`, `pagination-links`, `toast-container`, `seo-head`; `hero`, `services-grid`, `testimonials-carousel`, `cta-banner`, `featured-projects`, `stats`, `section-heading`, and the three card components; `/styleguide`.

**Still to do in Phase 2:**
- A dedicated `mobile-menu` component (currently inline in the navbar) and a `cookie-banner` component, both listed in the required structure.
- Component tests — Phase 2's stated deliverable. There are currently no tests asserting components render with required props, produce accessible markup (labels, ARIA), or that the navbar renders both desktop and mobile layouts. Add `tests/Feature/ComponentsTest.php`.
- Audit every component against the "all states" rule and fill any gaps found.
- Extend `/styleguide` to cover anything added.

## Non-negotiables — unchanged from Phase 1

No silent failures, validate at every boundary, every component has all states, accessibility is required (keyboard, focus, ARIA, 44px targets, 4.5:1 contrast), no hardcoded user-facing strings (use `lang/en/*.php`), no `$request->all()`, no external placeholder images, no `{!! !!}` for user-controlled content, no TODOs left unimplemented.

## Delivery

- This session is bound to its own `arena/…` branch. **Commit and push only to that branch**, and open the Phase 2 PR from it against `main`. Do not create `phase-2-design-system` — Arena tracks the session by its branch name.
- Do not start Phase 3 until Phase 2 tests pass.
- Finish by writing `docs/NEXT-SESSION-PROMPT.md` for Phase 3, replacing this file.

## Two things carried over from Phase 1

1. **`.github/ci/ci.yml` still needs moving to `.github/workflows/ci.yml`** by someone with the `workflows` permission — the automation token cannot write there. Mention it again in the Phase 2 PR if it is still outstanding.
2. **`composer.lock` is gitignored** because Packagist is unreachable here. It should be generated with `composer update` in an environment with Packagist access and committed.
