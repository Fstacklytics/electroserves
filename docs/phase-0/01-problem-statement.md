# Four-Question Problem Statement — ElectroServes

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

## 1. What problem does this software solve?

ElectroServes is an electronics and electrical services company based in Dar es Salaam, Tanzania. Currently, the company lacks a professional online presence to showcase its services, build credibility with potential clients, and provide an easy way for customers to learn about and request their services.

**The website solves:**
- **Visibility**: Potential customers cannot find ElectroServes online when searching for electrical and electronics services in their area.
- **Credibility**: Without a professional website, the company appears less trustworthy compared to competitors with established web presences.
- **Information Access**: Customers have no centralized place to learn about the full range of services offered, pricing expectations, certifications, and past work.
- **Content Management**: Company administrators need a simple, non-technical way to update website content (services, projects, team info, announcements) without developer involvement.

---

## 2. Who has this problem?

| User Group | Problem |
|---|---|
| **Potential Customers** (homeowners, businesses, institutions) | Cannot discover ElectroServes services online; have no way to evaluate the company's credibility or request services digitally |
| **Existing Customers** | No reference point for service details, contact information, or company updates |
| **ElectroServes Management/Admin** | No easy way to update marketing content, showcase new projects, or communicate service changes without technical help |
| **ElectroServes Technicians/Staff** | No professional platform to showcase their expertise and certifications |

---

## 3. How does this software solve it?

A modern, beautiful, responsive website built with **Laravel** (as the server-side rendering engine using Blade templates) and **Decap CMS** (as the git-based headless content management system):

- **Laravel** serves as the web framework, rendering pages with Blade templates, reading content from Markdown/YAML files, handling routing, and serving static assets.
- **Decap CMS** provides an admin interface at `/admin` where authorized personnel can create, edit, and publish content (services, projects, blog posts, team bios, testimonials) — all stored as Markdown/YAML files in the Git repository.
- **No database** is required for content storage — all content lives as flat files in Git, providing version control, audit trails, and zero database maintenance.
- **Modern UI** built with Tailwind CSS and Alpine.js for interactivity, ensuring the site is beautiful, fast, and accessible on all devices.

---

## 4. How will we know it works?

| Success Metric | Target |
|---|---|
| **Site loads within** | < 2.5 seconds (LCP) on 3G networks |
| **Mobile usability** | 100% of pages pass Google Mobile-Friendly test |
| **Content updates** | Admin can publish content changes within 5 minutes via Decap CMS |
| **Accessibility** | WCAG 2.1 AA compliance |
| **Core Web Vitals** | LCP < 2.5s, CLS < 0.1, INP < 200ms |
| **Uptime** | 99.5% availability |
| **SEO** | All pages indexable with proper meta tags, structured data, and sitemap |

---

*Document Version: 1.0*  
*Date: 2026-08-11*  
*Author: ElectroServes Development Team*
