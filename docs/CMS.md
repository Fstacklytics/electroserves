# Decap CMS — Editor Runbook

How editors use the content manager at `/admin`, and exactly what each action
does to the deployed site. This is the companion to
[`ARCHITECTURE.md`](ARCHITECTURE.md) §6.

**Mental model:** there is no database and no instant update. Every **Save**
commits a change to the `main` branch and triggers the **Netlify Production
build**. The public HTML only changes after that build finishes and is
**Published** (green). Plan on a short wait, not a live refresh.

---

## 1. Sign in

1. Go to **`/admin`** on the production site.
2. Click **Login** → **Netlify Identity**.
3. Sign in with the email/password from your Identity invitation.

   - Invitation and confirmation emails link to the site root with a hash
     token (e.g. `#invite_token=…`). The homepage loads the Netlify Identity
     widget, processes the token, and bounces you to `/admin`. Open the email
     link from a normal browser tab — not an incognito window that blocks the
     widget — and then hard-refresh `/admin` after accepting the invite.

There is **no** separate Git/GitHub login: the `git-gateway` backend pushes
through Netlify Identity, which has scoped push access to this repository.

---

## 2. Create / Edit

1. Pick a collection (Services, Projects, Blog, Hero Slides, Testimonials,
   Team, FAQs, Pages, Site Settings).
2. **New entry** (folder collections only) or open an existing one.
3. Fill the form and press **Save**.
4. Decap commits the file to `main` with a `content(create|update): …`
   message. Netlify rebuilds; the change appears on the next **Published**
   Production deploy.

Uploaded images land in `public/uploads/` (served at `/uploads/…`) and are
committed alongside the content. Do **not** change `media_folder` /
`public_folder` — the build and `src/lib/content.ts` depend on that path.

---

## 3. Show an entry (Publish)

Set **Published = On** (the default) and **Save**. The next Production build
includes the entry. This is the only "publish" action — there is no separate
Publish button.

---

## 4. Hide an entry (Unpublish)

Set **Published = Off** and **Save**. The next Production build **skips** the
entry, because `src/lib/content.ts` filters out `published: false`. The file
stays in Git (history is preserved); it simply does not render.

- Use the **Visible / Hidden** view filters in the collection sidebar to see
  which entries are currently shown or hidden.
- To bring it back, set **Published = On** and **Save** again.

There is **no "Unpublish" menu**. The old editorial-workflow
**Ready / Publish / Unpublish** controls were removed because the Unpublish
action tried to update a stale `cms/*` branch and failed with
`API_ERROR: Update is not a fast forward`. Hiding is now a toggle, not an
action on a branch.

---

## 5. Delete

- **Allowed** for any normal entry in Services, Projects, Blog, Hero Slides,
  Testimonials, Team, FAQs.
- **Not allowed** for:
  - **Site Settings** (single-file collection — `delete: false`).
  - **Pages** (About, Privacy Policy, Terms — `delete: false`). These pages
    are structural/legal and must not be removed from the site.

Deleting commits the removal to `main` and triggers a rebuild, like any other
Save.

---

## 6. View live

The **View live** button (and the site link in the CMS header) always opens
`https://zippy-kitten-7cad33.netlify.app` — the value of `site_url` /
`display_url` in `public/admin/config.yml`. It does **not** open "whatever host
you happened to open `/admin` on". Keep those two URLs aligned with the host
that actually serves this Netlify site (and with `SITE_URL` in `netlify.toml`).

> `electroserves.co.tz` has no public DNS yet (2026-08-12). Until that domain
> is added in Netlify → Domain management, resolves, and is set as the primary
> domain with TLS, all three values stay on `https://zippy-kitten-7cad33.netlify.app`.
> Switch them together when DNS/TLS/primary are proven.

---

## 7. After you Save — wait for green

1. Save in the CMS.
2. Open **Netlify → Deploys**. Watch for the build triggered by your commit.
3. Confirm the deploy is **Published** (green) and shows your commit SHA.
4. **Hard-refresh** the production URL (Cmd/Ctrl+Shift+R) to bypass the CDN
   cache, then check the page.

Because the site is static, an edit is never visible until step 3 completes.
If a change does not appear:

- Check the build log for `[content:warn]` / `[content:error]` — a YAML file
  saved as `.md`, or a missing required field, is skipped silently (the page
  shows its empty state, the build does not fail).
- Confirm `published: true` (Visible filter) — an Off entry is intentionally
  hidden.
- Confirm you are looking at the **primary** host in `site_url`, not a stale
  tab.

---

## 8. Stale `cms/*` branches

If the CMS ever reports `API_ERROR: Update is not a fast forward`, a leftover
`cms/*` branch from an earlier session is blocking the push. The
`.github/workflows/prune-cms-branches.yml` workflow deletes those branches
automatically on every push to `main` (and can be run manually from the
Actions tab). After a push to `main` clears them, **hard-refresh `/admin`** and
retry the Save.
