# Data Model Diagram — ElectroServes

---

## Overview

ElectroServes uses a **file-based data model** — all content is stored as Markdown files with YAML frontmatter or standalone YAML/JSON files in the Git repository. Decap CMS manages these files through its admin interface. Laravel reads these files at request time to render pages.

There is **no relational database**. The "data model" is defined by the structure of content files and their YAML schemas.

---

## Entity Relationship Diagram (Text-Based)

```
┌──────────────────┐       ┌──────────────────┐
│   SiteSettings   │       │    Navigation    │
│   (single file)  │       │   (single file)  │
├──────────────────┤       ├──────────────────┤
│ site_name        │       │ items[]          │
│ tagline          │       │   - label        │
│ phone            │       │   - url          │
│ email            │       │   - children[]   │
│ address          │       └──────────────────┘
│ social_links{}   │
│ logo             │
│ favicon          │
│ copyright        │
└──────────────────┘

┌──────────────────┐       ┌──────────────────┐
│     Service      │       │     Project      │
│   (collection)   │◀──────│   (collection)   │
├──────────────────┤  ref  ├──────────────────┤
│ title            │       │ title            │
│ slug             │       │ slug             │
│ icon             │       │ category         │
│ short_desc       │       │ description      │
│ full_description │       │ images[]         │
│ features[]       │       │ completion_date  │
│ price_range      │       │ client_name      │
│ image            │       │ location         │
│ order            │       │ services_used[]  │──→ Service.slug
│ is_active        │       │ featured         │
└──────────────────┘       │ order            │
                           └──────────────────┘

┌──────────────────┐       ┌──────────────────┐
│    BlogPost      │       │   Testimonial    │
│   (collection)   │       │   (collection)   │
├──────────────────┤       ├──────────────────┤
│ title            │       │ client_name      │
│ slug             │       │ company          │
│ author           │       │ rating (1-5)     │
│ date             │       │ quote            │
│ category         │       │ photo            │
│ tags[]           │       │ service_used     │──→ Service.slug
│ body (markdown)  │       │ order            │
│ featured_image   │       │ is_active        │
│ excerpt          │       └──────────────────┘
│ published        │
└──────────────────┘

┌──────────────────┐       ┌──────────────────┐
│   TeamMember     │       │      FAQ         │
│   (collection)   │       │   (collection)   │
├──────────────────┤       ├──────────────────┤
│ name             │       │ question         │
│ role             │       │ answer           │
│ bio              │       │ category         │
│ photo            │       │ order            │
│ certifications[] │       └──────────────────┘
│ email            │
│ phone            │
│ order            │
└──────────────────┘

┌──────────────────┐       ┌──────────────────┐
│   HeroSlider     │       │   Page (Static)  │
│   (collection)   │       │   (collection)   │
├──────────────────┤       ├──────────────────┤
│ heading          │       │ title            │
│ subheading       │       │ slug             │
│ image            │       │ body (markdown)  │
│ cta_text         │       │ meta_description │
│ cta_link         │       │ featured_image   │
│ order            │       │ layout           │
└──────────────────┘       └──────────────────┘
```

---

## File Structure

```
content/
├── settings/
│   ├── site.yml              # SiteSettings (single file)
│   └── navigation.yml        # Navigation structure (single file)
├── pages/
│   ├── home.yml              # Homepage content/hero
│   ├── about.md              # About page
│   ├── contact.yml           # Contact page info
│   ├── privacy-policy.md     # Privacy policy
│   ├── terms.md              # Terms of service
│   └── faq.yml               # FAQs
├── services/
│   ├── residential-electrical.md
│   ├── commercial-electrical.md
│   ├── electronics-repair.md
│   ├── installations.md
│   └── emergency-services.md
├── projects/
│   ├── project-alpha.md
│   ├── project-beta.md
│   └── ...
├── blog/
│   ├── 2026-01-15-first-post.md
│   └── ...
├── testimonials/
│   ├── testimonial-001.yml
│   └── ...
├── team/
│   ├── john-doe.yml
│   └── ...
└── uploads/                  # Media files managed by Decap
    ├── images/
    │   ├── services/
    │   ├── projects/
    │   ├── team/
    │   └── blog/
    └── ...
```

---

## Data Flow

```
Content Admin → Decap CMS → Git Commit → File System → Laravel → HTML Response
                              (GitHub)      (Markdown/     (Blade
                                              YAML)       Templates)
```

---

## Content Lifecycle

| Entity | Created By | Stored As | Read By | Updated By | Deleted By |
|---|---|---|---|---|---|
| SiteSettings | Developer (initial), Admin | YAML file | Laravel (every page) | Admin via CMS | Never |
| Navigation | Developer (initial), Admin | YAML file | Laravel (every page) | Admin via CMS | Never |
| Service | Admin via CMS | Markdown + frontmatter | Laravel (services pages) | Admin via CMS | Admin via CMS |
| Project | Admin via CMS | Markdown + frontmatter | Laravel (projects pages) | Admin via CMS | Admin via CMS |
| BlogPost | Admin via CMS | Markdown + frontmatter | Laravel (blog pages) | Admin via CMS | Admin via CMS |
| Testimonial | Admin via CMS | YAML file | Laravel (testimonials) | Admin via CMS | Admin via CMS |
| TeamMember | Admin via CMS | YAML file | Laravel (about page) | Admin via CMS | Admin via CMS |
| FAQ | Admin via CMS | YAML file | Laravel (FAQ page) | Admin via CMS | Admin via CMS |
| HeroSlider | Admin via CMS | YAML file | Laravel (homepage) | Admin via CMS | Admin via CMS |
| Media/Images | Admin via CMS | Git repository files | Laravel (img tags) | Admin via CMS | Admin via CMS |

---

*Document Version: 1.0*  
*Date: 2026-08-11*
