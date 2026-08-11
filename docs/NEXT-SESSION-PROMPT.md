# Next session — Phase 5 prompt

Copy everything below the line into a new Arena chat to continue with Phase 5.

---

Continue the **ElectroServes** build in `Fstacklytics/electroserves`. **Phases 0–4 are complete.** Read the existing implementation before changing it, then execute only Phase 5 from `docs/IMPLEMENTATION-PROMPT.md`.

## First, read these

1. `docs/IMPLEMENTATION-PROMPT.md` — the Phase 5 section. **Repository phase numbering wins** over any generic checklist you may also be given; use a generic checklist only as an applicability rubric.
2. `docs/PHASE-4-GAP-MATRIX.md` — the Phase 4 audit, every decision with its reasoning, the measured numbers, the defects found, and the precise list of checks that **could not** be run in the sandbox.
3. `docs/phase-0/*` for architecture, scope boundary, threat model, SLOs and CMS decisions.
4. `deploy/README.md` and `deploy/nginx.conf` before touching anything deployment-related.
5. The middleware, layouts, image component/service, Alpine components, CSS, content and tests — before proposing replacements.

## Phase 4 completion state

Phase 4 extended the existing architecture rather than rebuilding it. Landed on `arena/019ff099-electroserves` as commit `b3e3be5`.

**Response caching (4.1).** `app/Services/ResponseCacheService.php` and `app/Http/Middleware/ResponseCacheMiddleware.php`, configured by the `response_cache` block in `config/electroserves.php`. GET/HEAD only; per-route TTLs (home 600s, services/projects 1800s, blog 600s, static 3600s, legal 86400s, sitemap 3600s, default 600s); responses carry `X-Response-Cache: HIT|MISS|BYPASS`. Built on Laravel's own `Cache` facade — no `spatie/laravel-responsecache`.

- **Invalidation is implicit.** The cache key embeds a content fingerprint, so editing any file under `content/` changes every key and stale entries become unreachable. There is no purge step to forget. `content:flush` and `responsecache:clear` exist in `routes/console.php`.
- **Two non-obvious details worth knowing before you touch this.** Every `web` response carries Symfony's synthesised `Cache-Control: no-cache, private` plus `XSRF-TOKEN`/`electroserves_session` cookies. The service skips exactly that default string and exactly those two cookies, while still refusing any *other* `no-store`/`private` or any additional cookie. Loosen that and you will start caching personalised responses. The middleware also sits *inside* the `web` group on purpose, so the session is still readable — see the defect note below for what happens outside it.
- Contact page and submission are excluded by name, with a CSRF-token backstop so a future route cannot become cacheable by accident.

**Accessibility (4.2).** `tests/Unit/ColourContrastTest.php` computes WCAG relative-luminance ratios arithmetically for the token pairs actually used. It found **neutral-500 on neutral-100 at 4.34, below AA**; the test pins muted text to a white background so that pairing cannot be reintroduced. `tests/Feature/FormAccessibilityTest.php` follows a real rejected POST through the redirect and asserts the error wiring end-to-end.

Note one deliberate trade-off it pins down: `bootstrap/app.php` declares `dontFlash(['name','email','phone','message'])`, so contact PII is **never** repopulated after a validation failure. Empty fields there are a privacy decision, not a bug. Do not "fix" them without reading that test.

**Progressive enhancement (4.3).** Every server-sent control either works without JavaScript or is marked `data-js-only` and hidden by a rule inlined in the existing `<noscript>` block. Filtering stays client-side and current-page-only by design (`lang/en/projects.php` `filter_scope` documents this).

**Deployment (4.4/4.5).** `deploy/nginx.conf` and a 16-section `deploy/README.md`. Two deliberate **non**-duplications: security headers are set only by `SecurityHeadersMiddleware`, because a single `add_header` inside a `location` silently drops every inherited header; and there is **no `fastcgi_cache`**, because page caching belongs in the app, which knows which routes carry CSRF and session state. Nginx rate-limit zones are deliberately looser than the app limiter (5/hour per IP) so Laravel returns its own translated 429.

**Content (4.6).** `tests/Feature/ContentIntegrityTest.php` runs against the **real** `content/` directory, not fixtures. Counts verified: 6 services, 6 projects, 4 blog posts, 6 testimonials, 5 team, 10 FAQs, 3 hero slides. No filler, no remote images (the CSP is `img-src 'self' data:`, so a remote image would be blocked outright), consistent contact details, no future-dated posts.

**A real defect was found and fixed.** The `Cache-Control: no-store` branch in `SecurityHeadersMiddleware` was dead code. That middleware is registered globally, so it unwinds *outside* the `web` group; by the time it ran, `StartSession` had already saved the session and `Store::save()` → `ageFlashData()` had forgotten the `errors` key. Pages rendering validation errors — which also carry a CSRF token — were served with `no-cache` instead of `no-store`, meaning a shared cache was permitted to store them. It now reads the error bag `ShareErrorsFromSession` shares with the view factory, which nothing ages.

## Measured state at the end of Phase 4

| Metric | End of Phase 3 | End of Phase 4 |
|---|---|---|
| Tests / assertions | 369 / 1,297 | **498 / 2,786** |
| Production CSS+JS, pre-gzip | 148,144 B | **148,989 B** (+845 B; 29.8% of the 500 KB budget) |

`npm run lint:js`, `npm run lint:css`, `npm run build`, PHP syntax across 68 files, Blade compilation and `git diff --check` all pass. `npm audit` reports 0 vulnerabilities. Both lint scripts were **broken** before Phase 4 (no config file existed); enabling stylelint surfaced two real CSS defects, fixed at source.

**Do not let these regress.** Treat the test count, assertion count, content counts and bundle budget as floors.

## Checks that could NOT be run — do not report these as passing

- **`nginx -t`** — nginx is not installed in the sandbox. `deploy/nginx.conf` has 27 config assertions behind it, but its syntax has never been validated by nginx itself. Validate on a real host before activation.
- **`composer audit` / `composer install`** — Packagist is unreachable from the sandbox.
- **`php artisan test`** — not a registered command in this repository. The suite runs via `vendor/bin/phpunit`.
- **Real browser and screen-reader testing** — no browser in the environment. Everything accessibility- and compatibility-related in Phase 4 is an automated assertion or a source audit, and is labelled as such. Do not upgrade those claims.

## Scope — Phase 5 only

Execute only the Phase 5 section of `docs/IMPLEMENTATION-PROMPT.md`. Start by writing a gap matrix for it, in the style of `docs/PHASE-4-GAP-MATRIX.md`, then implement every applicable missing item. Do not stop after the audit, and do not begin Phase 6.

## Standing constraints

Carried forward and still in force:

- Work only on the Arena-assigned branch for your session. Do not invent a branch name.
- Do **not** introduce: authentication/authorization, a database or migrations, MSW, role switching, TypeScript, OpenAPI, feature-flag infrastructure, admin dashboards, payments, jQuery or a heavy frontend framework, external placeholder-image services, or heavy caching/analysis dependencies without a measured need.
- Phase 0 scope is a public, file-backed Laravel website. Mark non-applicable checklist items with a concise, scope-tied reason.
- Validate all external input server-side; never `$request->all()`; no unsafe raw Blade output of user-controlled content; no hardcoded user-facing strings (use `lang/en/*.php`); no hardcoded internal URLs (use named routes/config); no suppressed errors or empty catch blocks; never silently weaken CSP, CSRF, cache privacy or rate limiting.
- Test behaviour, not implementation strings. Fix root causes instead of weakening tests.
- Never report an unexecuted check as passing. State precisely why it could not run.
- No feature may be left incomplete or hidden behind a flag.

## Environment notes

The sandbox has **no system PHP and no Composer**, and Packagist, Sury and the Debian mirrors are unreachable. Vendor bootstrap uses a local shim; the runtime is **PHP 8.5.8** while `composer.json` requires `^8.3`, so be aware of that drift when reading test output. `composer.lock` is deliberately **absent** and must never be hand-written — generate it where Packagist is reachable.

Two runtime gotchas that will cost you a session if you rediscover them the hard way:

- **`$this->artisan(...)` hard-crashes the php-wasm runtime** and kills the whole PHPUnit run. Use `Artisan::call($name, $args, new BufferedOutput())` instead. `ResponseCacheTest::runCommand()` shows the pattern.
- **`public/build/` must exist** before running the suite, or 184 tests fail. Run `npm run build` first.

## Carried-over repository issue — needs a human

`.github/ci/ci.yml` is still in the wrong place. GitHub only reads workflows from `.github/workflows/`, so **CI is not running on this repository at all.**

This was attempted in Phase 4 and the push was rejected: `refusing to allow a GitHub App to create or update workflow .github/workflows/ci.yml without workflows permission`. The agent token lacks the `workflows` scope, so this cannot be fixed by an agent session. **A human with write access needs to run `git mv .github/ci/ci.yml .github/workflows/ci.yml`.** Re-flag this in every PR until it is done.
