# ElectroServes Website

> A modern, beautiful website for an electronics and electrical services company, built with Laravel + Decap CMS.

---

## 📋 Project Overview

**ElectroServes** is an electronics and electrical services company based in Dar es Salaam, Tanzania. This project delivers a professional, responsive, and accessible marketing website with a git-based content management system.

### Tech Stack

| Layer | Technology |
|---|---|
| Framework | Laravel 11 (PHP 8.3) |
| Templates | Blade |
| CSS | Tailwind CSS 3 |
| Interactivity | Alpine.js 3 |
| CMS | Decap CMS (git-based) |
| Content Storage | Markdown + YAML files in Git |
| Build Tool | Vite |
| Git Hosting | GitHub |
| CI/CD | GitHub Actions |
| Deployment | VPS (Nginx + PHP-FPM) |
| Database | None (file-based) |

---

## 🏗️ Architecture

```
Visitor → Nginx (HTTPS) → PHP-FPM → Laravel → Blade Templates
                                              ↓
                                    Read Markdown/YAML
                                    from content/ directory
                                              ↓
                                    Render HTML Response

Content Admin → /admin → Decap CMS → GitHub OAuth → Git Commit → content/ files
```

---

## 📁 Project Structure

```
electroserves/
├── app/
│   ├── Http/
│   │   ├── Controllers/       # Page controllers (HomeController, ServiceController, etc.)
│   │   └── Middleware/        # Custom middleware
│   ├── Services/
│   │   ├── ContentService.php # Reads and parses Markdown/YAML content files
│   │   └── MarkdownService.php # Markdown to HTML conversion
│   └── Models/                # Content data objects (not Eloquent)
├── content/                   # ALL CMS-MANAGED CONTENT LIVES HERE
│   ├── settings/              # Site settings (YAML)
│   ├── hero/                  # Hero slides (YAML)
│   ├── services/              # Service pages (Markdown + frontmatter)
│   ├── projects/              # Project case studies (Markdown + frontmatter)
│   ├── blog/                  # Blog posts (Markdown + frontmatter)
│   ├── testimonials/          # Customer testimonials (YAML)
│   ├── team/                  # Team member profiles (YAML)
│   ├── faqs/                  # FAQ entries (YAML)
│   ├── pages/                 # Static pages (Markdown + frontmatter)
│   └── uploads/               # Media files (images)
├── public/
│   ├── admin/                 # Decap CMS admin interface
│   │   ├── index.html         # CMS entry point
│   │   └── config.yml         # CMS configuration
│   └── uploads/               # Symlink to content/uploads
├── resources/
│   ├── views/
│   │   ├── layouts/           # Base layouts (app, auth, error)
│   │   ├── components/        # Reusable Blade components
│   │   │   ├── ui/            # Design system primitives (button, card, badge)
│   │   │   ├── sections/      # Page sections (hero, features, cta, footer)
│   │   │   └── common/        # Shared components (navbar, footer, sidebar)
│   │   ├── pages/             # Page templates (home, about, contact, etc.)
│   │   └── partials/          # Partial templates
│   ├── css/
│   │   └── app.css            # Tailwind CSS entry point
│   └── js/
│       └── app.js             # Alpine.js entry point
├── routes/
│   └── web.php                # All web routes
├── config/
│   └── electroserves.php      # App-specific configuration
├── tests/
│   ├── Feature/               # Feature tests (HTTP tests)
│   └── Unit/                  # Unit tests (ContentService, etc.)
├── .github/
│   └── workflows/
│       └── ci.yml             # CI/CD pipeline
├── tailwind.config.js
├── vite.config.js
├── composer.json
├── package.json
└── README.md
```

---

## 🚀 Getting Started

### Prerequisites
- PHP 8.3+
- Composer
- Node.js 18+
- npm

### Installation

```bash
# Clone the repository
git clone https://github.com/Fstacklytics/electroserves.git
cd electroserves

# Install PHP dependencies
composer install

# Install JS dependencies
npm install

# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate

# Build assets
npm run build

# Start development server
php artisan serve

# In another terminal, watch for asset changes
npm run dev
```

### Decap CMS Local Development

```bash
# Start the Decap CMS local proxy server
npx decap-server

# Access CMS at http://localhost:8080/admin
```

---

## 📄 Pages

| Page | Route | Content Source |
|---|---|---|
| Homepage | `/` | `content/hero/`, `content/services/`, `content/testimonials/` |
| Services | `/services` | `content/services/` |
| Service Detail | `/services/{slug}` | `content/services/{slug}.md` |
| About Us | `/about` | `content/pages/about.md`, `content/team/` |
| Projects | `/projects` | `content/projects/` |
| Project Detail | `/projects/{slug}` | `content/projects/{slug}.md` |
| Blog | `/blog` | `content/blog/` |
| Blog Post | `/blog/{slug}` | `content/blog/{slug}.md` |
| Testimonials | `/testimonials` | `content/testimonials/` |
| Contact | `/contact` | `content/settings/site.yml` |
| FAQ | `/faq` | `content/faqs/` |
| Privacy Policy | `/privacy-policy` | `content/pages/privacy-policy.md` |
| Terms | `/terms` | `content/pages/terms.md` |
| CMS Admin | `/admin` | Decap CMS interface |

---

## 🎨 Design System

### Colors (Tailwind config)
```
Primary:    Blue (#1E40AF → #3B82F6)
Secondary:  Amber/Orange (#D97706 → #F59E0B)
Success:    Green
Warning:    Yellow
Error:      Red
Neutral:    Slate scale
```

### Typography
```
Headings: Inter (Bold/SemiBold)
Body: Inter (Regular/Medium)
Mono: JetBrains Mono (code snippets in blog)
```

### Spacing
```
Base unit: 4px (Tailwind default)
Sections: py-16 to py-24
Container: max-w-7xl mx-auto px-4 sm:px-6 lg:px-8
```

### Breakpoints
```
sm:  640px   (mobile landscape)
md:  768px   (tablet)
lg:  1024px  (small desktop)
xl:  1280px  (desktop)
2xl: 1536px  (large desktop)
```

---

## 🧪 Testing

```bash
# Run all tests
php vendor/bin/phpunit

# Run one suite
php vendor/bin/phpunit --testsuite Unit
php vendor/bin/phpunit --testsuite Feature

# Run JS tests
npm run test
```

---

## 📦 Deployment

```bash
# Via GitHub Actions (automatic on push to main)
# Or manually:
git pull origin main
composer install --no-dev --optimize-autoloader
npm run build
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## 📚 Documentation

All project documentation is in `/docs/`:

```
docs/
├── phase-0/
│   ├── 01-problem-statement.md      # Four-question problem statement
│   ├── 02-user-flows.md             # User flow diagrams
│   ├── 03-scope-boundary.md         # In/out of scope
│   ├── 04-data-model.md             # Data model diagram
│   ├── 05-pii-classification.md     # PII and data classification
│   ├── 06-architecture-decisions.md # ADRs (8 decisions)
│   ├── 07-technology-decision-log.md # Technology choices
│   ├── 08-threat-model.md           # Security threat model
│   ├── 09-slo-document.md           # SLOs and reliability
│   └── 10-decap-cms-config.yml      # CMS configuration
└── phase-1/                         # Foundation documents (next)
```

---

## 📋 Development Checklist Progress

- [x] Phase 0 — Decisions (all deliverables complete)
- [x] Phase 1 — Foundation (Laravel 11 app, ContentService, DataObjects, all routes,
      security headers, env validation, Decap admin, sample content, CI, 153 tests)
- [x] Phase 2 — Design System (full component library with all states, layout shells,
      section components, `/styleguide`, component + accessibility tests, 359 tests)
- [ ] Phase 3 — Dev Layer
- [ ] Phase 4 — Build
- [ ] Phase 5 — Gate
- [ ] Phase 6 — Integration
- [ ] Phase 7 — Hardening
- [ ] Phase 8 — Launch

---

*Last Updated: 2026-08-11*
