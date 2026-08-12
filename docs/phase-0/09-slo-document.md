# SLO (Service Level Objectives) Document — ElectroServes

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

## Service Definition

**Service**: ElectroServes public-facing website  
**Description**: Marketing and information website for an electronics and electrical services company, including content management via Decap CMS.

---

## Service Level Indicators (SLIs)

| SLI | Measurement | Tool |
|---|---|---|
| **Availability** | Successful HTTP 2xx responses / total requests | Uptime monitoring (UptimeRobot, Hetrix) |
| **Latency (TTFB)** | Time to First Byte for page requests | Synthetic monitoring |
| **LCP** | Largest Contentful Paint | WebPageTest / Lighthouse CI |
| **CLS** | Cumulative Layout Shift | WebPageTest / Lighthouse CI |
| **INP** | Interaction to Next Paint | WebPageTest / Lighthouse CI |
| **Content Freshness** | Time between CMS publish and live on site | Manual check / webhook logging |
| **Error Rate** | 5xx responses / total responses | Nginx logs + error tracking |

---

## Service Level Objectives (SLOs)

| Objective | Target | Measurement Window | Rationale |
|---|---|---|---|
| **Availability** | ≥ 99.5% | Monthly | ~3.6 hours downtime/month acceptable for a marketing site |
| **TTFB (p95)** | < 800ms | Weekly | Server-rendered pages should respond quickly |
| **LCP (p75)** | < 2.5s | Weekly | Google Core Web Vitals "Good" threshold |
| **CLS (p75)** | < 0.1 | Weekly | Google Core Web Vitals "Good" threshold |
| **INP (p75)** | < 200ms | Weekly | Google Core Web Vitals "Good" threshold |
| **Error Rate** | < 1% | Daily | Very few server errors expected |
| **Content Freshness** | < 5 minutes | Per publish | Content updates should be near-instant |
| **SSL Certificate Validity** | > 14 days remaining | Daily | Auto-renewal should keep this well above |

---

## Error Budget

| SLO | Monthly Budget | Implication |
|---|---|---|
| Availability 99.5% | 3.65 hours/month | If budget exhausted: freeze non-critical deployments, investigate root cause |
| Error Rate < 1% | ~432 errors/month (at ~14k requests) | If budget exhausted: rollback recent changes, investigate |

---

## Reliability Constraints

### RPO (Recovery Point Objective)
- **Content**: Zero data loss — all content is in Git (version-controlled)
- **Server configuration**: 24 hours — configuration is in Git; server state restored from config
- **Logs**: Acceptable to lose up to 1 hour of logs

### RTO (Recovery Time Objective)
- **Full service restoration**: 1 hour from backup
- **Content restoration**: Immediate (Git revert)
- **DNS failover**: 15 minutes (TTL-based)

---

## Behaviour When Dependencies Are Unavailable

| Dependency | Unavailable Behaviour | User Impact |
|---|---|---|
| **GitHub** (Git host) | Site continues serving from local filesystem; CMS admin cannot publish | Visitors: None; Admins: Cannot update content |
| **GitHub OAuth** | CMS admin login fails; existing sessions may work | Visitors: None; Admins: Cannot log in to CMS |
| **Email Service** (SMTP) | Contact form shows error; suggests alternative contact method | Visitors: Cannot submit form; can still call/email directly |
| **Google Fonts** | Fallback system fonts used | Visitors: Slight visual difference; fully functional |
| **CDN** (if used) | Assets served from origin server | Visitors: Slightly slower asset loading |

---

## Monitoring Plan

### Uptime Monitoring
- **Tool**: UptimeRobot (free tier) or HetrixTools
- **Check interval**: Every 5 minutes
- **Check URL**: Homepage (GET, expect 200)
- **Alerting**: Email + SMS after 2 consecutive failures

### Performance Monitoring
- **Tool**: Lighthouse CI (in CI pipeline) + WebPageTest (monthly manual)
- **Frequency**: Every deployment + weekly automated
- **Thresholds**: LCP < 2.5s, CLS < 0.1, INP < 200ms

### Error Tracking
- **Tool**: Sentry or Laravel Flare
- **Coverage**: All PHP exceptions, JavaScript errors
- **Alerting**: Email on new error types; Slack on error rate spike

### Synthetic Monitoring
- **Tool**: Checkly or custom script
- **Checks**:
  - Homepage loads with 200 status
  - Services page loads with 200 status
  - Contact page loads with 200 status
  - Contact form submission works (test mode)
- **Frequency**: Every 10 minutes

---

## Incident Response

### Severity Levels

| Level | Definition | Response Time | Example |
|---|---|---|---|
| **P1 — Critical** | Site completely down, no workaround | 15 minutes | Server crash, DNS failure |
| **P2 — High** | Major feature broken, significant user impact | 1 hour | CMS broken, all pages erroring |
| **P3 — Medium** | Minor feature broken, workaround exists | 4 hours | Contact form failing, images not loading |
| **P4 — Low** | Cosmetic issue, minimal user impact | Next business day | Layout glitch, typo |

### Escalation Path
1. On-call developer (primary)
2. Backup developer (if primary unavailable)
3. External support (if internal team cannot resolve)

### Post-Mortem
- Required for all P1 and P2 incidents
- Template: What happened → Timeline → Root cause → Impact → Action items → Prevention
- Stored in `/docs/postmortems/` directory
- Shared with stakeholders within 48 hours

---

## Capacity Planning

### Current Expected Load
- **Daily visitors**: 50-500 (marketing site for local business)
- **Requests/day**: ~5,000-50,000
- **Peak concurrent**: ~20

### Scaling Thresholds
| Metric | Threshold | Action |
|---|---|---|
| CPU > 80% sustained 5min | Add response caching | Enable Laravel response cache |
| Memory > 80% | Investigate memory leaks | Profile PHP-FPM |
| Disk > 80% | Clean logs, expand storage | Rotate logs aggressively |
| Traffic 10x current | Consider CDN + caching | Add Cloudflare, Redis cache |

### Cost Model
| Traffic Level | Monthly Cost (estimated) |
|---|---|
| Current (1x) | $10-20 (VPS + domain) |
| 2x traffic | $10-20 (same VPS handles) |
| 10x traffic | $30-50 (larger VPS + CDN) |

---

*Document Version: 1.0*  
*Date: 2026-08-11*
