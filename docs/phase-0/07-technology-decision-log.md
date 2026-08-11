# Technology Decision Log — ElectroServes

---

## Summary Table

| Category | Technology | Version | Justification |
|---|---|---|---|
| **Language** | PHP | 8.3+ | Laravel requirement; modern PHP with JIT, enums, readonly classes |
| **Framework** | Laravel | 11.x | Full-featured PHP framework; Blade templates, routing, middleware |
| **Template Engine** | Blade | (Laravel built-in) | Server-side rendering; clean syntax; component support |
| **CMS** | Decap CMS | Latest (CDN) | Git-based; open-source; no database; clean admin UI |
| **CSS Framework** | Tailwind CSS | 3.x+ | Utility-first; design tokens; responsive; small production bundles |
| **JS Framework** | Alpine.js | 3.x+ | Lightweight; inline interactivity; no build step needed |
| **Build Tool** | Vite | 5.x+ | Fast HMR; Laravel Vite plugin; handles Tailwind + JS bundling |
| **Content Format** | Markdown + YAML frontmatter | — | Human-readable; Git-friendly; Decap CMS native |
| **Config Format** | YAML | — | For structured data (settings, testimonials, FAQs, team) |
| **Git Hosting** | GitHub | — | Code + content hosting; OAuth for Decap CMS; Actions for CI/CD |
| **CI/CD** | GitHub Actions | — | Free for public repos; integrates with GitHub; deploy automation |
| **Web Server** | Nginx | 1.24+ | Reverse proxy for PHP-FPM; static file serving; SSL termination |
| **PHP Handler** | PHP-FPM | 8.3+ | Process manager for PHP; OPcache support |
| **SSL/TLS** | Let's Encrypt | — | Free; auto-renewal; widely trusted |
| **Server OS** | Ubuntu | 22.04 LTS | LTS support; well-documented; large community |
| **Server Management** | Laravel Forge (optional) | — | Automates server provisioning, deployment, SSL |
| **Image Optimization** | Laravel Image/Intervention | 3.x | Resize, convert to WebP; optimize uploads |
| **Markdown Parser** | spatie/commonmark or league/commonmark | — | Parse Markdown content files to HTML |
| **YAML Parser** | symfony/yaml | — | Parse YAML config and data files |
| **Font** | Inter / Google Fonts | — | Modern, readable; good for UI text |
| **Icons** | Heroicons or Lucide | — | SVG icons; consistent style; Tailwind-compatible |
| **Analytics** | Plausible (optional) | — | Privacy-friendly; lightweight; no cookies |
| **Error Tracking** | Sentry or Flare | — | Laravel integration; production error monitoring |
| **Contact Form** | Laravel Mail + SMTP | — | Send form submissions via email |
| **Email Provider** | Mailgun / SMTP | — | Reliable email delivery for contact form |

---

## Technology Selection Criteria

### Why Laravel over alternatives?

| Criterion | Laravel | Next.js | Astro |
|---|---|---|---|
| Team familiarity | ★★★★★ | ★★★ | ★★ |
| File-based content reading | ★★★★★ | ★★★★ | ★★★★★ |
| Server requirements | Simple (PHP) | Node.js runtime | Static build |
| CMS integration ease | ★★★★★ | ★★★★ | ★★★★ |
| Long-term maintenance | ★★★★★ | ★★★★ | ★★★ |
| Community & ecosystem | ★★★★★ | ★★★★★ | ★★★★ |
| Performance (SSR) | ★★★★ | ★★★★★ | ★★★★★ |

### Why Decap CMS over alternatives?

| Criterion | Decap CMS | WordPress | Strapi | Statamic |
|---|---|---|---|---|
| Cost | Free | Free (hosting costs) | Free (hosting costs) | $259+/site |
| Database required | No | Yes (MySQL) | Yes (SQLite/PG) | Optional |
| Git-native | Yes | No | No | Optional |
| Admin UI quality | ★★★★ | ★★★★★ | ★★★★ | ★★★★★ |
| Content portability | ★★★★★ | ★★ | ★★★ | ★★★★ |
| Setup complexity | Low | Medium | Medium | Low |
| Community activity | Moderate | Very high | High | Moderate |

---

## Dependency Management

| Tool | Purpose | Configuration |
|---|---|---|
| **Composer** | PHP dependency management | `composer.json` with version constraints |
| **npm** | JavaScript/CSS dependency management | `package.json` with version constraints |
| **Dependabot** | Automated security updates | `.github/dependabot.yml` — weekly checks |
| **Laravel Vite Plugin** | Asset bundling integration | `vite.config.js` with Laravel plugin |

---

## Version Pinning Strategy

- **PHP**: `^8.3` in `composer.json`
- **Laravel**: `^11.0` in `composer.json`
- **Node.js**: `>=18.0` (LTS) specified in `.nvmrc`
- **Tailwind CSS**: `^3.4` in `package.json`
- **Alpine.js**: `^3.13` (CDN or npm)
- **Decap CMS**: CDN version pinned in admin HTML (update manually on security releases)

---

*Document Version: 1.0*  
*Date: 2026-08-11*
