# Scope Boundary Document — ElectroServes

> ⚠️ **Historical / superseded — not current operating documentation.**
> This document records the retired **Laravel 11 / Nginx + PHP-FPM 8.3 / Ubuntu**
> design and its implementation phases. That runtime has been replaced by a
> **fully static Astro site deployed to Netlify (Path B)** — the code,
> directories, build/test commands, env vars, caches, middleware and
> deployment runbooks described below **no longer exist and must not be used**.
> For the current system see **[`../ARCHITECTURE.md`](../ARCHITECTURE.md)** and
> [`../DEPLOY-NETLIFY-PATH-B.md`](../DEPLOY-NETLIFY-PATH-B.md). This file is
> retained only as a historical design record.

---


---

## In Scope (v1.0)

### Pages
| Page | Description |
|---|---|
| **Homepage** | Hero section, services overview, featured projects, testimonials carousel, CTA sections, trust badges |
| **Services** | Overview of all service categories with icons and descriptions |
| **Service Detail** (per service) | Detailed description, process steps, related projects, CTA |
| **About Us** | Company story, mission/vision, team profiles, certifications |
| **Projects / Portfolio** | Filterable gallery of completed projects with before/after |
| **Project Detail** | Individual project case study with images and description |
| **Blog** (listing) | Blog post listing with categories and search |
| **Blog Post** (detail) | Individual blog article with rich content |
| **Testimonials** | Customer reviews and ratings |
| **Contact** | Contact form, map, phone, email, address, business hours |
| **FAQ** | Frequently asked questions (accordion-style) |
| **Privacy Policy** | Legal privacy notice |
| **Terms of Service** | Legal terms |
| **404 Page** | Custom not-found page with navigation |
| **500 Page** | Custom error page |

### Content Management (Decap CMS)
| Collection | Fields Managed |
|---|---|
| **Site Settings** | Company name, tagline, phone, email, address, social links, logo |
| **Pages** | Title, slug, body content, meta description, featured image |
| **Services** | Title, slug, icon, short description, full description, features list, price range |
| **Projects** | Title, slug, category, description, images gallery, completion date, client name |
| **Blog Posts** | Title, slug, author, date, category, tags, body, featured image, excerpt |
| **Testimonials** | Client name, company, rating, quote, photo, service used |
| **Team Members** | Name, role, bio, photo, certifications |
| **FAQs** | Question, answer, category, order |
| **Hero/Slider** | Heading, subheading, image, CTA text, CTA link |

### Features
- Responsive design (mobile-first)
- Server-side rendering via Laravel Blade
- Content from Markdown/YAML files (read by Laravel)
- Decap CMS admin panel at `/admin`
- Contact form (submits via email or form service)
- Image optimization (WebP/AVIF with fallbacks)
- SEO meta tags and Open Graph tags
- XML sitemap generation
- Structured data (JSON-LD for LocalBusiness, Service, Organization)
- Accessibility (WCAG 2.1 AA)
- Dark/light mode toggle (optional)
- Multilingual support (Swahili + English) — optional v1.1

### Infrastructure
- Laravel application server
- Git repository (GitHub) for content + code
- Decap CMS with GitHub OAuth authentication
- CI/CD pipeline (GitHub Actions)
- Hosting: VPS or cloud (e.g., DigitalOcean, Hetzner, or similar)
- SSL/TLS certificate (Let's Encrypt)
- CDN for static assets (optional)

---

## Out of Scope (v1.0)

| Item | Reason |
|---|---|
| **User accounts / authentication** | Not needed for a marketing website |
| **E-commerce / online payments** | Services are quoted, not sold online |
| **Online booking / scheduling system** | Can be added in v2.0; use contact form for now |
| **Live chat widget** | Can be added in v2.0 |
| **Customer portal** | Not needed for a services showcase site |
| **Database-driven features** | Content is file-based via Decap CMS |
| **Real-time features** | Not applicable |
| **API endpoints** | Not needed; content served server-side |
| **Multi-tenant features** | Single business website |
| **Advanced analytics dashboard** | Use Google Analytics / Plausible externally |
| **CMS editorial workflow** | Basic publish workflow is sufficient for v1.0 |
| **Dynamic search** | Can use browser-based search on static content or add in v2.0 |

---

## Boundaries Diagram

```
┌─────────────────────────────────────────────────────────┐
│                    TRUST BOUNDARY                        │
│                                                         │
│  ┌─────────────┐     ┌─────────────┐                   │
│  │   Browser   │────▶│   Laravel   │                   │
│  │  (Visitor)  │◀────│   Server    │                   │
│  └─────────────┘     └──────┬──────┘                   │
│                             │                           │
│                    ┌────────┴────────┐                  │
│                    │  File System    │                  │
│                    │  (Markdown /    │                  │
│                    │   YAML files)   │                  │
│                    └────────┬────────┘                  │
│                             │                           │
└─────────────────────────────┼───────────────────────────┘
                              │
                    ┌─────────┴─────────┐
                    │  Git Repository   │
                    │    (GitHub)       │
                    └─────────┬─────────┘
                              │
                    ┌─────────┴─────────┐
                    │   Decap CMS       │
                    │   (/admin)        │
                    │  Content Admin    │
                    └───────────────────┘

External Dependencies:
  ├── GitHub (Git hosting + OAuth)
  ├── Email Service (contact form delivery)
  ├── CDN (optional, static assets)
  └── Google Fonts / Font service
```

---

*Document Version: 1.0*  
*Date: 2026-08-11*
