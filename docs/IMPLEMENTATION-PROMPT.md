# ElectroServes — Full Implementation Prompt

> **Copy this entire document into a new chat to begin implementation.**
> Replace `YOUR_REPO_URL` with your actual GitHub repository URL before starting.

---

## Context

You are implementing a complete, production-ready website for **ElectroServes**, an electronics and electrical services company based in Dar es Salaam, Tanzania.

**Repository:** `YOUR_REPO_URL`  
**Create a PR** for each phase against the `main` branch. Branch naming: `phase-{N}-{description}`.

All planning documents are in the repository at `docs/phase-0/`. Read them before writing any code. Specifically:

- `docs/phase-0/01-problem-statement.md` — what this software does
- `docs/phase-0/02-user-flows.md` — every user flow that must work
- `docs/phase-0/03-scope-boundary.md` — what is in and out of scope
- `docs/phase-0/04-data-model.md` — every entity, its fields, and lifecycle
- `docs/phase-0/05-pii-classification.md` — data sensitivity rules
- `docs/phase-0/06-architecture-decisions.md` — 8 ADRs explaining every technology choice and its trade-offs
- `docs/phase-0/07-technology-decision-log.md` — exact technology stack with versions
- `docs/phase-0/08-threat-model.md` — security requirements and mitigations
- `docs/phase-0/09-slo-document.md` — performance and reliability targets
- `docs/phase-0/10-decap-cms-config.yml` — complete CMS collection definitions

---

## Technology Stack (Exact Versions)

| Layer | Technology | Version |
|---|---|---|
| Language | PHP | 8.3+ |
| Framework | Laravel | 11.x |
| Templates | Blade | (Laravel built-in) |
| CSS | Tailwind CSS | 3.4+ |
| JS Interactivity | Alpine.js | 3.13+ |
| Build Tool | Vite | 5.x (via Laravel Vite Plugin) |
| CMS | Decap CMS | Latest (CDN) |
| Markdown Parser | league/commonmark | 2.x |
| YAML Parser | symfony/yaml | 7.x |
| Image Processing | intervention/image | 3.x |
| Git Hosting | GitHub | — |
| CI/CD | GitHub Actions | — |
| Web Server | Nginx + PHP-FPM | — |

---

## ABSOLUTE IMPLEMENTATION RULES

These rules are non-negotiable. Every single file, function, component, and feature must comply. If you find yourself skipping any of these, stop and implement them before moving on.

### Rule 1: No Silent Failures — Ever

- Every function that can fail MUST return or throw a meaningful result.
- Never use `@` to suppress PHP errors.
- Never catch an exception and do nothing with it.
- Never assume an external call succeeded — check the response.
- Never assume a file exists — check before reading.
- Never assume a YAML/Markdown file is well-formed — parse with error handling.
- If content file is missing, malformed, or has missing required fields → the page shows a graceful, user-friendly fallback (not a 500 error, not a blank page). Log the specific issue.

```php
// ❌ WRONG — silent failure
$content = Yaml::parseFile($path);
return $content['title'];

// ✅ CORRECT — explicit error handling
if (!file_exists($path)) {
    Log::warning("Content file not found: {$path}");
    return null;
}

try {
    $content = Yaml::parseFile($path);
} catch (\Exception $e) {
    Log::error("Failed to parse YAML: {$path}", ['error' => $e->getMessage()]);
    return null;
}

if (!isset($content['title'])) {
    Log::warning("Missing 'title' field in: {$path}");
    return null;
}

return $content['title'];
```

### Rule 2: Validate Everything at Every Boundary

- **Form input**: Validate on the server with Laravel FormRequest classes. Every field has explicit rules (required, string, max, email, etc.). Show inline error messages on the frontend.
- **Content files**: Validate that required frontmatter fields exist before using them. Define a schema for each content type.
- **Configuration**: Validate `.env` at boot. App must not start if required variables are missing.
- **API responses** (if any): Validate structure before consuming.
- **Image uploads** (CMS): Validate type, size, and dimensions.

```php
// ❌ WRONG — no validation
public function store(Request $request) {
    Mail::send('emails.contact', $request->all(), ...);
}

// ✅ CORRECT — full validation
public function store(ContactFormRequest $request) {
    $validated = $request->validated();
    // ... use $validated, never $request->all()
}

class ContactFormRequest extends FormRequest {
    public function rules(): array {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20', 'regex:/^[0-9+\-\s()]+$/'],
            'service_type' => ['required', 'string', Rule::in(ServiceCategory::values())],
            'message' => ['required', 'string', 'max:5000'],
        ];
    }
}
```

### Rule 3: Every UI Component Has ALL States

Every interactive or data-driven component MUST implement ALL of these states. If any state is missing, the component is incomplete.

| State | Implementation |
|---|---|
| **Default** | The normal, resting state |
| **Loading** | Skeleton/spinner during async operations; buttons disabled with loading indicator |
| **Success** | Confirmation message or state change |
| **Error** | Human-readable message, recovery action (retry button, alternative path) |
| **Empty** | Helpful message explaining why empty, with CTA if user can fix it |
| **Disabled** | Visually distinct (opacity + cursor), `aria-disabled="true"`, no click handler fires |
| **Hover** | Visual feedback on mouseover |
| **Focus** | Visible focus ring for keyboard navigation |
| **Active** | Visual feedback during click/press |

### Rule 4: Every Feature Includes These Deliverables

When you implement a feature, you are NOT done until ALL of these exist:

1. ✅ Working implementation (happy path)
2. ✅ Error handling (every failure path)
3. ✅ Input validation (server-side always, client-side for UX)
4. ✅ Loading state in UI
5. ✅ Empty state in UI
6. ✅ Error state in UI with recovery path
7. ✅ Accessibility attributes (ARIA labels, roles, keyboard navigation)
8. ✅ Responsive behavior (mobile, tablet, desktop)
9. ✅ Feature test (PHPUnit) covering happy path + at least 2 error paths
10. ✅ No hardcoded strings visible to users (use config or translation files)
11. ✅ SEO meta tags for any public-facing page
12. ✅ Logging for any operation that can fail silently

### Rule 5: Accessibility Is Not Optional

- Every interactive element is keyboard operable (Tab, Enter, Escape, Arrow keys)
- Every interactive element has a visible focus indicator (never `outline: none` without replacement)
- Every form input has an associated `<label>` element
- Every image has meaningful `alt` text (or `alt=""` for decorative images)
- Color is never the only indicator of meaning
- `aria-live` regions announce dynamic content changes
- `aria-busy="true"` on loading containers
- `aria-disabled="true"` on disabled elements
- Focus is trapped inside modals/dialogs
- Focus returns to trigger element when modal closes
- Skip-to-content link on every page
- Touch targets minimum 44×44px
- Color contrast ratio minimum 4.5:1 for normal text, 3:1 for large text

### Rule 6: Performance Budgets

Every page must meet these targets:

| Metric | Target |
|---|---|
| LCP | < 2.5 seconds |
| CLS | < 0.1 |
| INP | < 200ms |
| TTFB | < 800ms |
| Total page weight | < 500KB (HTML + CSS + JS, excluding images) |
| Image format | WebP with JPEG/PNG fallback |
| Image loading | `loading="lazy"` for below-fold; explicit width/height on all images |
| JS | Alpine.js only; no jQuery, no heavy frameworks |
| CSS | Tailwind purge enabled; no unused styles in production |

### Rule 7: Security Non-Negotiables

- CSRF token on every form (Laravel `@csrf`)
- Output escaping: use `{{ }}` in Blade (never `{!! !!}` unless explicitly needed for trusted Markdown HTML, and only after sanitization)
- Content Security Policy header configured
- HTTPS enforced; HTTP redirects to HTTPS
- No secrets in source code (`.env` in `.gitignore`)
- Contact form: honeypot field + rate limiting (max 5 submissions per IP per hour)
- All user input sanitized before use
- Security headers: X-Content-Type-Options, X-Frame-Options, Referrer-Policy, Permissions-Policy, HSTS
- Directory listing disabled in Nginx config
- PHP error display OFF in production

---

## Implementation Order

Implement in this exact order. Do not skip ahead. Each phase builds on the previous.

---

### Phase 1: Foundation

Create a PR named `phase-1-foundation`.

#### 1.1 — Laravel Project Initialization
- Fresh Laravel 11 installation (no database — remove database migrations, Eloquent default setup)
- Remove unnecessary default packages (database-related)
- Configure `.env.example` with all required variables documented
- Configure `.gitignore` to exclude: `.env`, `node_modules/`, `vendor/`, `storage/`, `public/hot`, `.idea/`, `.vscode/`

#### 1.2 — Project Structure
Create this exact directory structure:

```
app/
  Http/
    Controllers/
      Pages/
        HomeController.php
        ServiceController.php
        ProjectController.php
        BlogController.php
        AboutController.php
        ContactController.php
        FaqController.php
        TestimonialController.php
    Requests/
      ContactFormRequest.php
    Middleware/
      SecurityHeadersMiddleware.php
  Services/
    ContentService.php          ← reads and caches content files
    MarkdownService.php         ← parses markdown to HTML
    YamlService.php             ← parses YAML files
    SeoService.php              ← generates meta tags
    ImageService.php            ← handles image paths and optimization
  ViewModels/
    HomeViewModel.php           ← prepares data for home view
    ServiceViewModel.php
    ProjectViewModel.php
    BlogViewModel.php
    AboutViewModel.php
    ContactViewModel.php
    FaqViewModel.php
  DataObjects/
    Service.php                 ← typed value object
    Project.php
    BlogPost.php
    Testimonial.php
    TeamMember.php
    Faq.php
    HeroSlide.php
    SiteSettings.php
content/
  settings/
    site.yml
    seo.yml
  hero/
  services/
  projects/
  blog/
  testimonials/
  team/
  faqs/
  pages/
  uploads/
    images/
public/
  admin/
    index.html                  ← Decap CMS entry
    config.yml                  ← copy from docs/phase-0/10-decap-cms-config.yml
resources/
  views/
    layouts/
      app.blade.php             ← base layout
      error.blade.php           ← error layout
    components/
      ui/
        button.blade.php
        card.blade.php
        badge.blade.php
        input.blade.php
        textarea.blade.php
        select.blade.php
        alert.blade.php
        skeleton.blade.php
        spinner.blade.php
      sections/
        hero.blade.php
        services-grid.blade.php
        testimonials-carousel.blade.php
        cta-banner.blade.php
        featured-projects.blade.php
        stats.blade.php
      common/
        navbar.blade.php
        footer.blade.php
        mobile-menu.blade.php
        breadcrumb.blade.php
        pagination.blade.php
        seo-head.blade.php
        skip-to-content.blade.php
        cookie-banner.blade.php
      icons/
        ← SVG icon components
    pages/
      home.blade.php
      services/
        index.blade.php
        show.blade.php
      projects/
        index.blade.php
        show.blade.php
      blog/
        index.blade.php
        show.blade.php
      about.blade.php
      contact.blade.php
      faq.blade.php
      testimonials.blade.php
      privacy-policy.blade.php
      terms.blade.php
    errors/
      404.blade.php
      500.blade.php
      403.blade.php
  css/
    app.css                     ← Tailwind entry + custom styles
  js/
    app.js                      ← Alpine.js entry + custom components
config/
  electroserves.php             ← app-specific config (content paths, cache TTL, etc.)
routes/
  web.php                       ← all routes defined
tests/
  Feature/
    PagesTest.php               ← every page returns 200
    ContactFormTest.php         ← form validation + submission
    ContentServiceTest.php      ← content reading with error cases
  Unit/
    DataObjects/
      ServiceTest.php
      BlogPostTest.php
```

#### 1.3 — Tailwind CSS + Vite Setup
- Install and configure Tailwind CSS with Laravel Vite Plugin
- Define design tokens in `tailwind.config.js`:
  - Colors: Primary (blue scale), Secondary (amber scale), semantic (success/warning/error/info), neutral (slate scale)
  - Typography: Inter font family, type scale (ratio 1.25), weights (400, 500, 600, 700)
  - Spacing: 4px base unit
  - Border radius: none, sm (4px), md (8px), lg (12px), xl (16px), full
  - Shadows: sm, md, lg, xl
  - Breakpoints: sm (640), md (768), lg (1024), xl (1280), 2xl (1536)
- Configure content paths for Blade files
- Build succeeds with `npm run build`
- Dev server works with `npm run dev` and HMR

#### 1.4 — Alpine.js Setup
- Install Alpine.js via npm
- Configure in `resources/js/app.js`
- Register reusable Alpine components: `dropdown`, `modal`, `accordion`, `tabs`, `toast`
- Each component handles: open/close, keyboard navigation (Escape to close), focus management, aria attributes

#### 1.5 — Content Services
Implement `ContentService`, `MarkdownService`, `YamlService` with:

```
ContentService MUST:
  - Read Markdown files with YAML frontmatter
  - Read standalone YAML files
  - Parse into typed DataObjects (not raw arrays)
  - Filter by published status
  - Sort by order field or date
  - Cache parsed results (configurable TTL)
  - Handle missing files gracefully (log warning, return null/empty collection)
  - Handle malformed YAML/Markdown gracefully (log error, return null)
  - Handle missing required fields gracefully (log warning, skip that entry)
  - NEVER throw unhandled exceptions to the controller layer
```

#### 1.6 — DataObjects
Create typed PHP 8.3 value objects for every entity. Use readonly classes with constructor promotion. Each DataObject:
- Has a static `fromArray(array $data): ?self` factory method that validates required fields
- Returns null (with logging) if required fields are missing — never throws
- Has typed properties for every field
- Has computed/helper methods (e.g., `BlogPost::formattedDate()`)

#### 1.7 — Routes
Define all routes in `web.php`:

```php
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/services', [ServiceController::class, 'index'])->name('services.index');
Route::get('/services/{slug}', [ServiceController::class, 'show'])->name('services.show');
Route::get('/projects', [ProjectController::class, 'index'])->name('projects.index');
Route::get('/projects/{slug}', [ProjectController::class, 'show'])->name('projects.show');
Route::get('/blog', [BlogController::class, 'index'])->name('blog.index');
Route::get('/blog/{slug}', [BlogController::class, 'show'])->name('blog.show');
Route::get('/about', [AboutController::class, 'index'])->name('about');
Route::get('/testimonials', [TestimonialController::class, 'index'])->name('testimonials');
Route::get('/faq', [FaqController::class, 'index'])->name('faq');
Route::get('/contact', [ContactController::class, 'index'])->name('contact');
Route::post('/contact', [ContactController::class, 'store'])->name('contact.store');
Route::get('/privacy-policy', [PageController::class, 'privacyPolicy'])->name('privacy');
Route::get('/terms', [PageController::class, 'terms'])->name('terms');
```

#### 1.8 — CI Pipeline
Create `.github/workflows/ci.yml`:

```yaml
# On every push and PR:
# 1. PHP: composer install → php -l (lint) → php artisan test
# 2. Node: npm ci → npm run build
# 3. Security: composer audit → npm audit (fail on high)
# 4. Secret scan: gitleaks detect
```

#### 1.9 — Environment Validation
Create a service provider that validates required `.env` variables at boot. The app MUST fail to start with a clear error message if any required variable is missing.

Required variables:
```
APP_NAME
APP_ENV
APP_KEY
APP_URL
MAIL_MAILER
MAIL_HOST
MAIL_PORT
MAIL_USERNAME
MAIL_PASSWORD
MAIL_FROM_ADDRESS
MAIL_FROM_NAME
CONTACT_NOTIFICATION_EMAIL
```

#### 1.10 — Security Headers Middleware
Implement middleware that adds all security headers (from threat model document). Register globally.

#### 1.11 — Sample Content
Create at least 3 sample entries for each content collection so the site is not empty during development. Content should be realistic (not Lorem Ipsum).

#### 1.12 — Decap CMS Admin
- Create `public/admin/index.html` with Decap CMS loaded from CDN
- Copy `config.yml` from `docs/phase-0/10-decap-cms-config.yml` to `public/admin/config.yml`
- Verify admin page loads at `/admin`

#### Tests for Phase 1
- Every route returns 200
- Content service handles missing file gracefully
- Content service handles malformed YAML gracefully
- Content service handles missing required fields gracefully
- Contact form rejects empty submission with validation errors
- Security headers present on every response
- App fails to boot without required env variables

---

### Phase 2: Design System

Create a PR named `phase-2-design-system`.

#### 2.1 — UI Components
Build every component in `resources/views/components/ui/` with ALL states:

**button.blade.php:**
- Props: `variant` (primary, secondary, outline, ghost, danger), `size` (sm, md, lg), `loading` (bool), `disabled` (bool), `type`, `href`
- States: default, hover, focus (ring), active, disabled (opacity + aria-disabled + pointer-events-none), loading (spinner + text hidden)
- Renders as `<a>` when href provided, `<button>` otherwise
- Min height 44px on all sizes

**card.blade.php:**
- Props: `hoverable`, `padding`, `bordered`
- Slots: `header`, default, `footer`
- Hover state: shadow elevation change + slight translateY

**input.blade.php / textarea.blade.php / select.blade.php:**
- Props: `label`, `name`, `id`, `type`, `placeholder`, `required`, `error`, `helpText`, `value`
- States: default, focus (ring), error (red border + error message below), disabled
- Every input has a `<label for="id">` — no exceptions
- Error message uses `aria-describedby` linked to input

**alert.blade.php:**
- Props: `type` (success, warning, error, info), `dismissible`, `title`
- ARIA: `role="alert"` for error/warning, `role="status"` for success/info
- Dismiss button with `aria-label="Dismiss"`

**skeleton.blade.php:**
- Animated pulse loading placeholder
- Props: `width`, `height`, `rounded`, `lines` (for text skeletons)

**spinner.blade.php:**
- SVG animated spinner
- Props: `size` (sm, md, lg), `color`
- `aria-label="Loading"` and `role="status"`

#### 2.2 — Layout Shells

**app.blade.php (base layout):**
- `<!DOCTYPE html>` with `lang` attribute
- `<head>`: charset, viewport, CSP meta, SEO meta slot, favicon, preconnect for fonts, Vite assets
- `<body>`: skip-to-content link, navbar slot, main content slot with `id="main-content"`, footer slot, toast container, Alpine.js, flash messages
- Dark/light mode support via class on `<html>`

**navbar.blade.php:**
- Logo + company name
- Desktop: horizontal nav links with active state indicator
- Mobile: hamburger button → slide-in menu with all links
- Sticky on scroll (with shadow)
- "Get a Quote" CTA button always visible
- Keyboard: Tab through links, Escape closes mobile menu
- `aria-current="page"` on active link
- `aria-expanded` on hamburger button

**footer.blade.php:**
- Company info + tagline
- Quick links (Services, About, Contact, Blog)
- Contact info (phone, email, address)
- Social media links with `aria-label` and `rel="noopener noreferrer"`
- Copyright text
- Back-to-top button

**error.blade.php:**
- Layout for error pages (404, 500, 403)
- Illustration/icon, error code, human-readable message, "Go Home" button, "Contact Support" link

#### 2.3 — Section Components

**hero.blade.php:**
- Full-width hero with background image (with overlay for text readability)
- Heading (h1), subheading, primary CTA, optional secondary CTA
- Responsive: text size adjusts, image scales
- `loading="eager"` on hero image (it's above the fold)

**services-grid.blade.php:**
- Grid of service cards (2 cols mobile, 3 cols tablet, 4 cols desktop)
- Each card: icon, title, short description, "Learn More" link
- Empty state: "No services available yet" message

**testimonials-carousel.blade.php:**
- Auto-rotating testimonials with manual navigation (dots + arrows)
- Each: quote, client name, company, rating stars, photo
- Keyboard: Arrow keys to navigate, pause on focus/hover
- `aria-roledescription="carousel"`, `aria-live="polite"` on active slide
- Respects `prefers-reduced-motion`

**cta-banner.blade.php:**
- Full-width call-to-action section
- Heading, description, button
- Variant: default, emergency (red/urgent styling)

**featured-projects.blade.php:**
- Grid of project cards with images
- "View All Projects" link at bottom
- Empty state: "No projects to showcase yet"

#### 2.4 — Living Reference Page
Create `/styleguide` route (dev-only, hidden in production) that renders every component in every state for visual review.

#### Tests for Phase 2
- Every component renders with required props
- Every component renders accessible markup (labels, aria attributes)
- Navbar renders desktop and mobile layouts
- Footer renders all sections

---

### Phase 3: Build All Pages

Create a PR named `phase-3-pages`.

#### 3.1 — Homepage (`/`)
Sections in order:
1. **Hero** — rotating slides from `content/hero/` (use testimonials carousel component pattern)
2. **Services Overview** — grid from `content/services/` (max 8, "View All" link)
3. **Featured Projects** — grid from `content/projects/` where `featured: true` (max 6)
4. **Stats/Trust** — years in business, projects completed, happy clients (from site settings)
5. **Testimonials** — carousel from `content/testimonials/`
6. **CTA Banner** — "Ready to get started?" with link to contact

Empty states for each section if content is missing. Loading states for any lazy-loaded content.

#### 3.2 — Services Index (`/services`)
- Page heading with description
- Filter by category (All, Residential, Commercial, Electronics, Installation, Emergency) — client-side filter via Alpine.js
- Grid of service cards
- Empty state: "No services match your filter" + "Clear filter" button
- If no services exist at all: "Our services are being updated. Please contact us directly."

#### 3.3 — Service Detail (`/services/{slug}`)
- Breadcrumb: Home > Services > {Service Name}
- Hero image + title
- Full description (rendered Markdown, sanitized)
- Features list (checkmark icons)
- Price range (if available)
- Related projects (filtered by service)
- CTA: "Request a Quote for {Service Name}" → links to contact form pre-filled with service type
- 404 if slug not found (custom 404 page, not Laravel default)

#### 3.4 — Projects Index (`/projects`)
- Page heading
- Filter by category (same categories as services)
- Grid of project cards with image, title, location, date
- Pagination if > 12 projects
- Empty state per filter + global empty state

#### 3.5 — Project Detail (`/projects/{slug}`)
- Breadcrumb: Home > Projects > {Project Title}
- Featured image (full width)
- Image gallery (lightbox with keyboard navigation — Arrow keys, Escape to close)
- Project description, client, location, completion date
- Services used (linked to service detail pages)
- Related projects
- 404 if slug not found

#### 3.6 — Blog Index (`/blog`)
- Page heading
- Category filter tabs
- Blog post cards: featured image, title, excerpt, author, date, category badge
- Pagination (6 per page)
- Empty state: "No blog posts yet. Check back soon!"

#### 3.7 — Blog Post (`/blog/{slug}`)
- Breadcrumb: Home > Blog > {Post Title}
- Featured image, title, author, date, category, tags
- Body content (rendered Markdown, sanitized — allow headings, lists, links, images, code blocks; strip scripts and iframes)
- Table of contents (auto-generated from h2/h3 headings)
- Share buttons (copy link, Twitter, Facebook — open in new tab with `rel="noopener noreferrer"`)
- Related posts (same category, max 3)
- 404 if slug not found
- SEO: Article structured data (JSON-LD)

#### 3.8 — About Us (`/about`)
- Company story section (from `content/pages/about.md`)
- Mission and Vision
- Team grid from `content/team/` (photo, name, role, short bio)
- Certifications/licenses section
- Empty states for each section

#### 3.9 — Contact (`/contact`)
- Contact form (Name, Email, Phone, Service Type dropdown, Message)
- Form validation: client-side (Alpine.js for instant feedback) + server-side (FormRequest)
- Honeypot field (hidden from humans, bots fill it)
- Rate limiting: 5 submissions per IP per hour
- Loading state on submit button (spinner + "Sending...")
- Success state: "Thank you! We'll get back to you within 24 hours." with option to submit another
- Error state: "Something went wrong. Please try again or call us at {phone}."
- Contact info sidebar: phone (click-to-call), email (mailto), address, business hours
- Google Maps embed (if configured in site settings)
- SEO: LocalBusiness structured data

#### 3.10 — FAQ (`/faq`)
- Grouped by category with tabs or accordion sections
- Each FAQ: question (clickable header) + answer (expandable)
- Alpine.js accordion component with keyboard support
- "Still have questions?" CTA → contact page
- Empty state: "No FAQs yet. Contact us with your questions."
- SEO: FAQPage structured data (JSON-LD)

#### 3.11 — Testimonials (`/testimonials`)
- All testimonials in a masonry/grid layout
- Filter by service type (if tagged)
- Rating display with star icons
- Client photo, name, company, quote
- CTA: "Had a great experience? Share your feedback!" → contact page

#### 3.12 — Legal Pages
- Privacy Policy (`/privacy-policy`) — from `content/pages/privacy-policy.md`
- Terms of Service (`/terms`) — from `content/pages/terms.md`
- Both with proper heading hierarchy and last-updated date

#### 3.13 — Error Pages
- **404**: Custom illustration, "Page not found" message, search suggestion, "Go Home" button, "Browse Services" link
- **500**: "Something went wrong" message, "Try again" button, "Contact Support" link with email
- **403**: "Access denied" message, "Go Home" button
- All error pages use the error layout, include navbar and footer for navigation

#### 3.14 — SEO & Meta Tags
Every page must have:
- Unique `<title>` tag (from content or defaults)
- `<meta name="description">` (from content or defaults)
- Open Graph tags (og:title, og:description, og:image, og:url, og:type)
- Twitter Card tags
- Canonical URL
- JSON-LD structured data where applicable (Organization, LocalBusiness, Service, Article, FAQPage, BreadcrumbList)
- XML sitemap (generated from routes + dynamic content slugs)
- `robots.txt`

#### Tests for Phase 3
- Every page returns 200 with sample content
- Every page returns a graceful response (not 500) when content is empty
- Service detail returns 404 for non-existent slug
- Project detail returns 404 for non-existent slug
- Blog post returns 404 for non-existent slug
- Contact form validates all fields
- Contact form rejects honeypot submissions
- Contact form rate limits after 5 attempts
- Contact form sends email on valid submission
- All pages have title tag, meta description, OG tags
- All pages have skip-to-content link
- All pages pass HTML validation (no unclosed tags, no duplicate IDs)

---

### Phase 4: Polish & Hardening

Create a PR named `phase-4-polish`.

#### 4.1 — Performance Optimization
- Enable Laravel response caching (configurable per-page TTL)
- Image optimization: all `<img>` tags have `width`, `height`, `loading="lazy"` (except hero), `decoding="async"`
- Preconnect hints for external domains (fonts, analytics)
- Inline critical CSS for above-the-fold content
- Font display: swap (no invisible text during font load)
- Bundle analysis: verify no unnecessary dependencies
- Compress assets (gzip/brotli via Nginx config)

#### 4.2 — Accessibility Audit
- Run through every page with keyboard only (Tab, Shift+Tab, Enter, Escape, Arrow keys)
- Verify every interactive element has visible focus
- Verify all ARIA attributes are correct
- Verify color contrast on all text (use a contrast checker)
- Verify skip-to-content link works
- Verify modals/drawers trap focus and return focus on close
- Add `prefers-reduced-motion` support (disable animations)

#### 4.3 — Browser Compatibility
- Test markup for compatibility with: Chrome 90+, Firefox 90+, Safari 14+, Edge 90+
- No browser-specific CSS without fallbacks
- Progressive enhancement: site works without JS (content visible, navigation works via standard links)

#### 4.4 — Nginx Configuration
Provide a production-ready Nginx config file in `deploy/nginx.conf` with:
- SSL/TLS configuration (modern cipher suite)
- Security headers
- Gzip compression
- Static asset caching (long TTL for hashed files)
- PHP-FPM proxy configuration
- Directory listing disabled
- Rate limiting for contact form endpoint

#### 4.5 — Deployment Documentation
Create `deploy/README.md` with:
- Server requirements
- Step-by-step deployment guide
- Environment variable reference
- SSL setup with Let's Encrypt
- Decap CMS OAuth setup with GitHub
- Backup strategy (Git handles content; server config backed up separately)

#### 4.6 — Content Population
Ensure `content/` directory has realistic sample content:
- 5+ services with full descriptions
- 6+ projects with images and descriptions
- 3+ blog posts
- 5+ testimonials
- 4+ team members
- 8+ FAQs
- Complete site settings
- 3 hero slides
- About page content
- Privacy policy and terms of service

---

## Definition of Done (Per Feature)

A feature is NOT done until:

```
[ ] Works correctly in the happy path
[ ] All error cases handled with user-friendly messages
[ ] Edge cases identified and handled
[ ] Input validated on server (FormRequest) and client (Alpine.js where applicable)
[ ] Loading state implemented
[ ] Empty state implemented with helpful message
[ ] Error state implemented with recovery path
[ ] Disabled state visually distinct and non-interactive
[ ] Keyboard navigable (Tab, Enter, Escape)
[ ] Screen reader announces correctly (ARIA labels and roles)
[ ] Responsive on mobile (320px), tablet (768px), desktop (1280px)
[ ] No hardcoded user-facing strings
[ ] SEO meta tags present
[ ] Feature test written (happy path + 2 error paths minimum)
[ ] No new linting errors
[ ] No new TypeScript/PHP type errors
[ ] Builds successfully (npm run build + php artisan test)
```

---

## File Naming Conventions

- **Blade components**: `kebab-case.blade.php` (e.g., `service-card.blade.php`)
- **Controllers**: `PascalCase.php` (e.g., `ServiceController.php`)
- **DataObjects**: `PascalCase.php` (e.g., `BlogPost.php`)
- **Services**: `PascalCaseService.php` (e.g., `ContentService.php`)
- **Tests**: `PascalCaseTest.php` (e.g., `ContentServiceTest.php`)
- **Content files**: `kebab-case.md` or `kebab-case.yml` (e.g., `residential-electrical.md`)
- **CSS/JS**: `kebab-case` (e.g., `app.css`, `app.js`)
- **Routes**: `kebab-case` URLs (e.g., `/privacy-policy`)

---

## Sample Content Requirements

All sample content MUST be:
- Realistic and professional (no Lorem Ipsum, no "test" content)
- Relevant to an electrical and electronics services company in Tanzania
- Written in English (with optional Swahili translations noted for future)
- Include proper frontmatter with all required fields
- Include at least one entry with missing optional fields (to test graceful handling)

---

## What NOT To Do

- ❌ Do NOT implement features not listed in the scope boundary document
- ❌ Do NOT add a database — content is file-based
- ❌ Do NOT add user authentication for visitors
- ❌ Do NOT use jQuery or any heavy JS library
- ❌ Do NOT use `{!! !!}` in Blade for user-controlled content
- ❌ Do NOT skip error handling to "get it working faster"
- ❌ Do NOT leave TODO comments without implementing — implement it now
- ❌ Do NOT use placeholder images from external services (use solid color placeholders or inline SVGs)
- ❌ Do NOT hardcode URLs — use `route()` and `config()` helpers
- ❌ Do NOT create components that only work in one state — implement ALL states
- ❌ Do NOT write tests that only test the happy path — test error paths too
- ❌ Do NOT suppress errors with `@` operator or empty catch blocks
- ❌ Do NOT use `$request->all()` — always use validated data

---

## When You Finish Each Phase

1. Run all tests: `php artisan test` and `npm run build`
2. Verify zero test failures
3. Verify zero build errors
4. Review your own code against the Absolute Implementation Rules above
5. Create the PR with a descriptive title and summary of what was implemented
6. List any decisions or deviations from the plan in the PR description

---

## Starting Instructions

Begin with **Phase 1: Foundation**. Read all documents in `docs/phase-0/` first, then implement every item in Phase 1 in order. Create branch `phase-1-foundation`, implement everything, run tests, and create the PR.

Do not move to the next phase until the current phase is complete with all tests passing.

If anything is unclear, ask before guessing. If a document is missing information, flag it and propose a solution.

**Start now.**
