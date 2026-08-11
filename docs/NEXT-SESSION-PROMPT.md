# Next session — Phase 3 prompt

Copy everything below the line into a new Arena chat to continue with Phase 3.

---

Continue the **ElectroServes** build in `Fstacklytics/electroserves`. **Phases 1 and 2 are complete** — read what exists before writing anything, so you extend the design system rather than duplicating it.

## First, read these

1. `docs/phase-0/*` — the ten decision documents (data model, threat model, scope boundary, ADRs, SLOs, CMS config).
2. `docs/IMPLEMENTATION-PROMPT.md` — the full brief; **Phase 3 is the section "Phase 3: Build All Pages"**.
3. `docs/PHASE-1-SUMMARY.md` and `docs/PHASE-2-SUMMARY.md` — what exists and the conventions to follow.
4. The code: `app/Services/ContentService.php`, `app/DataObjects/`, `resources/views/components/`, `resources/views/pages/`, and `tests/`.

## Environment — read before running anything

This sandbox **cannot reach Packagist, deb.debian.org, or packages.sury.org**. There is no system PHP and `composer install` cannot use the default repository. The working toolchain is already set up — reuse it:

```bash
cd ~/electroserves
. ./.sandbox-toolchain.sh     # puts php + composer on PATH
php vendor/bin/phpunit        # 359 tests should pass
npm run build                 # should build clean
```

`php artisan serve` does **not** work (the WASM runtime cannot bind sockets). To preview, render pages through the HTTP kernel into `storage/preview/` and serve that directory with `python3 -m http.server`, as Phases 1–2 did.

## Scope — Phase 3 only

All 15 pages already render with real content, correct SEO and empty states. Phase 3 is about **completing** them, not rebuilding. Audit first, then close these gaps:

- **Pagination is not wired up.** `x-common.pagination-links` exists and is registered as the default paginator view, but `/projects` and `/blog` render full lists. Wire them to `LengthAwarePaginator` using `config('electroserves.pagination')` (12 projects, 6 blog posts). Note the interaction with the client-side category filters — decide and document whether filtering is per-page or across the set.
- **Blog post share buttons and TOC** exist; verify against the brief and add anything missing.
- **Project detail lightbox** exists; confirm keyboard navigation matches the brief.
- **Service detail "Request Quote"** already pre-fills via `?service=`; verify the full flow.
- Re-read the Phase 3 checklist line by line and close anything else outstanding.
- **Tests**: extend `PagesTest` for pagination (page 2 renders, out-of-range page behaves, filters persist). The brief's other Phase 3 test requirements are already covered by `PagesTest`, `ContactFormTest` and `AccessibilityTest` — verify rather than duplicate.

## Non-negotiables — unchanged

No silent failures · validate at every boundary · every component has all states · accessibility is required (keyboard, focus, ARIA, 44px targets, 4.5:1 contrast) · no hardcoded user-facing strings (use `lang/en/*.php`) · no `$request->all()` · no external placeholder images · no `{!! !!}` for user-controlled content · no TODOs left unimplemented.

## Delivery

- This session is bound to its own `arena/…` branch. **Commit and push only to that branch** and open the Phase 3 PR from it against `main`. Do not create `phase-3-pages` — Arena tracks the session by its branch name.
- Do not start Phase 4 until Phase 3 tests pass.
- Finish by rewriting `docs/NEXT-SESSION-PROMPT.md` for Phase 4.

## Carried over — still needs a human

1. **`.github/ci/ci.yml` must be moved to `.github/workflows/ci.yml`** by an account with the GitHub `workflows` permission; the automation token cannot write there. Flag it again in the PR if still outstanding.
2. **`composer.lock` is gitignored** because Packagist is unreachable here. Regenerate it with `composer update` somewhere with Packagist access and commit it.
