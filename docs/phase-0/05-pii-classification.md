# PII / Data Classification Table — ElectroServes

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

## Classification Levels

| Level | Description | Handling |
|---|---|---|
| **Public** | Information intended for public display | No special handling; can be cached, CDN-delivered |
| **Internal** | Information for company use only | Access-controlled; not exposed to public |
| **Confidential** | Sensitive business information | Encrypted at rest; strict access control |
| **PII** | Personally Identifiable Information | Full GDPR/compliance handling; consent required |
| **Sensitive** | Credentials, secrets, tokens | Encrypted; never in source code; rotated regularly |

---

## Data Classification

### Website Content (Public)

| Field | Classification | Stored In | Encryption | Retention |
|---|---|---|---|---|
| Company name, tagline | Public | Git (YAML) | None needed | Indefinite |
| Service descriptions | Public | Git (Markdown) | None needed | Indefinite |
| Project descriptions | Public | Git (Markdown) | None needed | Indefinite |
| Blog posts | Public | Git (Markdown) | None needed | Indefinite |
| Team member names & roles | Public | Git (YAML) | None needed | Until removed |
| Team member bios | Public | Git (YAML) | None needed | Until removed |
| Testimonials (client name, quote) | Public | Git (YAML) | None needed | Until removed |
| FAQs | Public | Git (YAML) | None needed | Indefinite |
| Published images/photos | Public | Git repository | None needed | Until removed |
| Service prices/ranges | Public | Git (Markdown) | None needed | Until updated |

### Contact Form Submissions (PII)

| Field | Classification | Stored In | Encryption | Retention | Deletion |
|---|---|---|---|---|---|
| Visitor name | PII | Email (transient) | TLS in transit | Until email deleted | User requests or 12 months |
| Visitor email | PII | Email (transient) | TLS in transit | Until email deleted | User requests or 12 months |
| Visitor phone | PII | Email (transient) | TLS in transit | Until email deleted | User requests or 12 months |
| Message content | Internal | Email (transient) | TLS in transit | Until email deleted | 12 months |
| IP address (server logs) | PII | Server logs | At rest (log encryption) | 30 days | Automatic rotation |

### Authentication / CMS Access (Sensitive)

| Field | Classification | Stored In | Encryption | Retention | Notes |
|---|---|---|---|---|---|
| GitHub OAuth tokens | Sensitive | Browser session (transient) | TLS in transit | Session duration | Never stored server-side |
| GitHub credentials | Sensitive | GitHub (external) | GitHub-managed | Per GitHub policy | Not our responsibility |
| Server SSH keys | Sensitive | Deployment system | Encrypted at rest | Until rotated | Rotate quarterly |
| Environment variables | Sensitive | Server .env file | File permissions (600) | Until changed | Never in source code |
| SSL/TLS certificates | Sensitive | Server filesystem | File permissions | Until expiry | Auto-renewed via Let's Encrypt |

### Server / Infrastructure (Confidential)

| Field | Classification | Stored In | Encryption | Retention |
|---|---|---|---|---|
| Server access logs | Confidential | Server filesystem | Encrypted volume | 30 days |
| Error tracking data | Confidential | Error tracking service | Service-managed | 90 days |
| Analytics data | Internal | Analytics provider | Service-managed | Per provider policy |
| Backup files | Confidential | Backup storage | Encrypted | 30 days rolling |

---

## Regulatory Considerations

| Regulation | Applicability | Requirement | Implementation |
|---|---|---|---|
| **Tanzania Personal Data Protection Act (2022)** | YES — collecting visitor PII via contact form | Consent, purpose limitation, data minimization | Privacy policy page; contact form only collects necessary fields |
| **GDPR** | Conditional — if serving EU visitors | Cookie consent, right to erasure, data portability | Cookie banner (if cookies used); privacy policy; data deletion process |
| **CCPA** | Low probability — unlikely California visitors | Opt-out rights | Privacy policy covers this |

---

## Privacy by Design Decisions

1. **No cookies** for tracking on initial launch (may add analytics later with consent banner)
2. **Contact form data** sent via email only — not stored in any database
3. **No user accounts** — no stored passwords or user profiles
4. **No third-party scripts** that track users (no Facebook Pixel, no Google Ads initially)
5. **Privacy policy** page is a required page before launch
6. **Team member PII** (personal emails, phones) — only publish what team members consent to make public

---

*Document Version: 1.0*  
*Date: 2026-08-11*
