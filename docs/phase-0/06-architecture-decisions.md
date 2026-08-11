# Architecture Decision Records (ADRs) — ElectroServes

---

## ADR-001: Use Laravel as the Server-Side Rendering Framework

**Status:** Accepted  
**Date:** 2026-08-11

### Context
We need a web framework to render the ElectroServes website. Options considered:
- **Next.js** (React-based SSR/SSG)
- **Laravel** (PHP framework with Blade templates)
- **Astro** (Content-focused SSG with islands architecture)
- **Hugo** (Go-based SSG)
- **Pure static HTML** with a build tool

### Decision
Use **Laravel** as the web framework with Blade templates for server-side rendering.

### Consequences
**Positive:**
- Team familiarity with PHP/Laravel ecosystem
- Blade templates are powerful and easy to reason about
- Laravel can read Markdown/YAML files natively (spatie/yaml-front-matter, symfony/yaml)
- Built-in routing, middleware, validation, and CSRF protection
- Easy to add features later (API endpoints, form handling)
- Large ecosystem of packages
- Well-documented and battle-tested

**Negative:**
- Requires PHP runtime on server (not purely static)
- Slightly higher server resource usage than pure static sites
- PHP deployment requires more server management than edge-deployed static sites

**Mitigation:**
- Laravel is lightweight when used without database/Eloquent
- Page caching can be added if needed (response cache middleware)
- VPS hosting is affordable and widely available

---

## ADR-002: Use Decap CMS for Content Management

**Status:** Accepted  
**Date:** 2026-08-11

### Context
We need a CMS that allows non-technical administrators to update website content. Options considered:
- **WordPress** (traditional CMS with database)
- **Strapi** (headless CMS with database)
- **Statamic** (Laravel-native flat-file CMS)
- **Decap CMS** (git-based, open-source, no database)
- **TinaCMS** (git-based, more modern but less mature)
- **Payload CMS** (headless, database-backed)

### Decision
Use **Decap CMS** as the content management system.

### Consequences
**Positive:**
- Zero database required — content stored as files in Git
- Free and open-source (MIT license)
- Provides a clean, web-based editorial interface
- Git-based = full version history, audit trail, and rollback capability
- Framework-agnostic — works with any SSG or framework
- GitHub OAuth for authentication — no user management needed
- Supports Markdown editing with preview
- Media management built-in
- Content changes trigger standard Git workflows

**Negative:**
- Development has slowed since Netlify handed it to community (2023)
- No built-in editorial workflow (PR-based workflow available but adds complexity)
- UI is functional but not as polished as commercial alternatives
- Requires GitHub OAuth setup (external dependency)
- No real-time collaboration

**Mitigation:**
- The project is stable and widely used (~18k GitHub stars)
- Static CMS (community fork) available as migration path if needed
- For a small team, the feature set is sufficient
- GitHub OAuth is reliable and well-maintained

---

## ADR-003: No Database — File-Based Content Storage

**Status:** Accepted  
**Date:** 2026-08-11

### Context
The website is primarily a marketing/information site. We need to decide whether to use a database.

### Decision
**No database.** All content is stored as Markdown files (with YAML frontmatter) and YAML/JSON files in the Git repository.

### Consequences
**Positive:**
- Zero database maintenance, backups, or migrations
- Content is version-controlled alongside code
- Developers can edit content directly in IDE if needed
- Git provides full audit history
- No database-related security vulnerabilities (SQL injection impossible)
- Simplified deployment (no DB provisioning)
- Content is portable — not locked into any CMS format

**Negative:**
- No relational queries (must load and filter in PHP)
- Performance degrades with very large content sets (1000s of files)
- No concurrent write support (mitigated by Git merge handling)
- Search requires loading all content into memory

**Mitigation:**
- This website will have < 200 content items — file-based is more than sufficient
- Laravel can cache parsed content in memory/file cache
- For search, a client-side solution (e.g., lunr.js) or simple string matching is sufficient
- Git handles concurrent edits via merge

---

## ADR-004: Tailwind CSS for Styling

**Status:** Accepted  
**Date:** 2026-08-11

### Context
We need a CSS framework/approach for the UI. Options considered:
- **Tailwind CSS** (utility-first)
- **Bootstrap** (component-based)
- **Bulma** (component-based)
- **Custom CSS** (from scratch)
- **CSS Modules** with a framework

### Decision
Use **Tailwind CSS** as the primary styling approach.

### Consequences
**Positive:**
- Utility-first approach enables rapid development
- Design tokens defined in `tailwind.config.js` (colors, spacing, typography)
- No CSS specificity wars
- Small production bundles via purging unused styles
- Works seamlessly with Laravel Blade
- Excellent documentation and community
- Responsive design utilities built-in
- Consistent design system enforced via config

**Negative:**
- HTML can become verbose with many utility classes
- Learning curve for developers unfamiliar with utility-first
- Requires build step (Tailwind CLI or PostCSS)

**Mitigation:**
- Use `@apply` for commonly repeated utility combinations
- Extract components into Blade components for reuse
- Team can learn quickly from documentation

---

## ADR-005: Alpine.js for Client-Side Interactivity

**Status:** Accepted  
**Date:** 2026-08-11

### Context
The website needs minimal client-side interactivity (mobile menu, accordions, modals, form validation). Options considered:
- **Alpine.js** (lightweight reactive framework)
- **Vanilla JavaScript** (no framework)
- **Vue.js** (full framework)
- **React** (full framework)
- **HTMX** (server-driven interactivity)

### Decision
Use **Alpine.js** for client-side interactivity.

### Consequences
**Positive:**
- Extremely lightweight (~15KB gzipped)
- Perfect for small interactive enhancements on server-rendered pages
- Works inline with HTML — no build step required (CDN available)
- Pairs naturally with Tailwind CSS and Laravel Blade
- Low learning curve
- Handles: dropdowns, modals, accordions, tabs, form validation, animations

**Negative:**
- Not suitable for complex SPA-like interactions
- Limited ecosystem compared to React/Vue
- State management limited to component scope

**Mitigation:**
- This website does not require complex client-side state
- All primary content is server-rendered; Alpine enhances UX only
- Can be replaced or supplemented later if needs grow

---

## ADR-006: GitHub for Git Hosting and OAuth

**Status:** Accepted  
**Date:** 2026-08-11

### Context
Decap CMS requires a Git backend and OAuth provider. Options:
- **GitHub** + GitHub OAuth
- **GitLab** + GitLab OAuth
- **Bitbucket** + Bitbucket OAuth
- **Netlify** + Git Gateway (requires Netlify hosting)

### Decision
Use **GitHub** as the Git host and **GitHub OAuth** for Decap CMS authentication.

### Consequences
**Positive:**
- Most widely supported by Decap CMS
- Reliable OAuth implementation
- Free for public and private repositories
- GitHub Actions available for CI/CD
- Large ecosystem of integrations

**Negative:**
- External dependency on GitHub availability
- GitHub account required for content administrators
- OAuth setup requires a GitHub OAuth App configuration

**Mitigation:**
- GitHub has > 99.9% uptime SLA
- Content admins are technical enough to have GitHub accounts
- OAuth setup is one-time configuration

---

## ADR-007: Deployment Model — VPS with Nginx + PHP-FPM

**Status:** Accepted  
**Date:** 2026-08-11

### Context
We need to decide how to deploy the Laravel application. Options:
- **Shared hosting** (cheap but limited)
- **VPS** (DigitalOcean, Hetzner, Vultr)
- **Serverless** (Laravel Vapor on AWS Lambda)
- **PaaS** (Forge, Envoyer, Platform.sh)
- **Container** (Docker + Kubernetes)

### Decision
Deploy on a **VPS** with Nginx + PHP-FPM, managed via **Laravel Forge** or manual configuration.

### Consequences
**Positive:**
- Full control over server configuration
- Cost-effective ($5-20/month for this workload)
- Can optimize PHP-FPM, OPcache, and Nginx for performance
- Simple deployment via Git pull + cache clear
- Can add page caching for near-static performance

**Negative:**
- Requires server administration knowledge
- Security patches must be applied manually (or via Forge)
- No automatic scaling

**Mitigation:**
- Laravel Forge automates server management ($12/month)
- For a low-traffic marketing site, a single VPS is sufficient
- Unattended upgrades can handle security patches
- Can migrate to containers or serverless later if traffic grows

---

## ADR-008: Rendering Strategy — Server-Side Rendering (SSR)

**Status:** Accepted  
**Date:** 2026-08-11

### Context
How should pages be rendered? Options:
- **SSR** (server renders HTML per request)
- **SSG** (pre-build all HTML at deploy time)
- **SPA** (client-side rendering)
- **Mixed** (SSG for static pages, SSR for dynamic)

### Decision
**Server-Side Rendering (SSR)** — Laravel renders each page on request, reading content from files.

### Consequences
**Positive:**
- Content changes are immediately visible (no rebuild needed)
- Decap CMS commits → content updated on next page load
- Simple architecture — no build step for content
- Full SEO support (HTML in response)
- Can add dynamic features later without re-architecting

**Negative:**
- Each request requires PHP execution (slower than static)
- Server must handle all traffic (no CDN edge caching by default)
- Higher server resource usage than SSG

**Mitigation:**
- Add response caching (Laravel's response cache) for near-static performance
- Can add Varnish/Redis caching layer if needed
- For a marketing site with low traffic, SSR performance is more than adequate
- Can add CDN caching with proper cache headers

---

*Document Version: 1.0*  
*Date: 2026-08-11*
