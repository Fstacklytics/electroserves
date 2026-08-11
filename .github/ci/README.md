# CI pipeline

`ci.yml` is the complete Phase 1 CI pipeline. It lives here rather than in
`.github/workflows/` because the automation token used to open this pull
request does not hold the GitHub `workflows` permission, so it cannot create
or modify files under `.github/workflows/`.

## Activating it

Move the file into place and commit it with an account that can write
workflows:

```bash
mkdir -p .github/workflows
git mv .github/ci/ci.yml .github/workflows/ci.yml
git commit -m "ci: activate the Phase 1 pipeline"
git push
```

No edits to the file are needed — it is ready to run as written.

## What it does

| Job | Steps |
|---|---|
| **php** | `composer install` → lint every PHP file → compile Blade templates → validate `public/admin/config.yml` parses and defines collections → validate every file in `content/` parses → `phpunit` |
| **frontend** | `npm ci` → `npm run build` → enforce the 500KB CSS + JS budget |
| **security** | `composer audit` → `npm audit --audit-level=high` → Gitleaks secret scan → assert no `.env` is committed |

The content and CMS-config validation steps exist because those two failure
modes are invisible at runtime: the application degrades gracefully when a
content file is malformed, and a broken `config.yml` produces a blank admin
screen with no server-side error. CI is where they need to be caught.
