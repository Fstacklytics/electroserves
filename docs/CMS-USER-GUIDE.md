# CMS User Guide (for non-technical editors)

This is a plain-language guide to editing the ElectroServes website through the
content manager at **`/admin`**. You do **not** need to know code, Git, or
servers to use it. If you can fill in a form and click **Save**, you can update
the site.

> **The one rule to remember:** the site is **not updated the instant you save**.
> Every change is rebuilt and republished by Netlify. After you **Save**, wait a
> minute or two, then refresh the live page (Cmd/Ctrl + Shift + R) to see it.

---

## 1. How to sign in

1. Open **`/admin`** on the website (e.g. the production site URL).
2. Click **Login with GitHub**.
3. A GitHub window opens — sign in to GitHub (or approve) and **Authorize** the
   site's app. If you are already signed in to GitHub, it may skip straight to
   the **Authorize** button.
4. You are taken back to the content manager, signed in.

Anyone with write access to the site's GitHub repository can sign in. If you
were just added as a collaborator, accept the GitHub invitation from your
email first, then open `/admin`.

---

## 2. The big idea: collections

The site content is organised into **collections** (like folders). Pick one from
the left, then either open an existing item or click **New …** to add one.

| Collection | What it controls on the site |
|---|---|
| **Hero Slides** | The big rotating banner at the top of the homepage (the "carousel"). |
| **Services** | The services listed on the Services page and homepage. |
| **Projects** | Customer projects with photos, shown on the Projects page. |
| **Blog** | News/articles on the Blog page. |
| **Testimonials** | Customer quotes on the homepage and Testimonials page. |
| **Team** | Team member profiles on the About page. |
| **FAQs** | Questions & answers on the FAQ page. |
| **Pages** | Standalone pages (About, Privacy Policy, Terms). |
| **Site Settings** | The business name, phone, email, hours, social links, SEO. |

---

## 3. Adding or editing content

1. Choose a collection.
2. Click an item to edit it, or **New …** to create one.
3. Fill in the boxes. Each box has a label; some have a small hint under them
   explaining what to type.
4. Click **Save** (top-right). The change is sent for the next site rebuild.

That's it. There is no "Publish" button — **Save** is the publish action.

---

## 4. Showing and hiding a page (the "Published" switch)

Every item has a **Published** switch (On/Off):

- **On (default)** → the item appears on the site after the next rebuild.
- **Off** → the item is hidden from the site after the next rebuild (but it is
  not deleted; you can turn it back On any time).

Use this instead of deleting something you might want later. You can also use the
**Visible / Hidden** filter at the top of a collection list to see what is
currently shown or hidden.

---

## 5. Uploading images (including many images in a carousel/gallery)

Images are uploaded into the site's `uploads` folder and referenced as
`/uploads/your-file.png`. You do **not** need to manage that folder yourself —
just use the image box.

- To add an image: click the image field, then **Upload** (pick a file from your
  computer) or **Browse** existing uploads. The file is stored automatically.
- You can upload **as many images as you like**.

### Homepage carousel (Hero Slides)
The homepage banner rotates through **Hero Slides**. Each slide holds **one**
background photo. To show several images in the carousel:

1. Go to **Hero Slides**.
2. Click **New Hero Slide** for each image you want.
3. On each slide, set the **Background Image** and a **Heading** (and optional
   text/buttons).
4. Use the **Order** number to set the sequence — **lower numbers show first**.
5. Make sure **Published** is On.

The site will then rotate through all your slides.

### Project photo gallery
On a **Project**, the **Image Gallery** is a list — click **Add** to create one
row per photo. Each row has an **Image** and an optional **Caption**:

- An image **with** a caption appears in the photo gallery (lightbox).
- An image **without** a caption (or a caption with no image) becomes a
  **"Project highlight"** text item.

Add as many rows as you need.

---

## 6. Deleting

- You **can** delete normal items in Services, Projects, Blog, Hero Slides,
  Testimonials, Team, and FAQs.
- You **cannot** delete **Site Settings** or **Pages** (About / Privacy /
  Terms) — those are protected so the site structure stays intact.
- Deleting removes the item from the site after the next rebuild (it is gone,
  not just hidden). If you only want to hide something, use the **Published**
  switch (section 4) instead.

---

## 7. After you Save — when will I see it?

1. Click **Save**.
2. Wait about 1–2 minutes for the site to rebuild and republish.
3. **Hard-refresh** the page (Cmd/Ctrl + Shift + R) to bypass the cache.
4. Still not there?
   - Check the item has **Published = On**.
   - Confirm you are looking at the correct page on the live site.
   - Wait a little longer — large changes can take a bit more time.

---

## 8. Quick tips

- **Keep headings short** and **write plain, friendly text**.
- **One idea per field** — don't paste a whole document into a short box.
- **Images:** clear, well-lit photos work best. Very large files slow the site
  down; resize before uploading if you can.
- **Don't panic about mistakes** — you can always re-open the item and Save
  again. To temporarily remove something, switch **Published** Off rather than
  deleting.
- If something looks wrong after a save, wait for the rebuild and hard-refresh
  before assuming the worst.

---

## 9. Where to get help

- This guide covers everyday editing.
- For the technical editor runbook (sign-in tokens, deploy status, the
  `cms/*` branch cleanup), see [`docs/CMS.md`](CMS.md).
- For how the site is built and deployed, see
  [`docs/ARCHITECTURE.md`](ARCHITECTURE.md).
