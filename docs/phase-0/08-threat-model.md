# Threat Model Document — ElectroServes

---

## Methodology

Lightweight threat model based on **OWASP Top 10 (2021)** and **OWASP API Security Top 10**, adapted for a file-based, server-rendered marketing website with no database and no user accounts.

---

## System Overview

```
Internet → Nginx (SSL) → PHP-FPM (Laravel) → File System (Git repo)
                                                    ↑
                                              Decap CMS → GitHub OAuth → GitHub
```

**Trust Boundaries:**
1. **Internet ↔ Web Server** (Nginx) — All external traffic crosses here
2. **Web Server ↔ File System** — Laravel reads content files
3. **CMS Admin ↔ GitHub** — OAuth authentication flow
4. **Server ↔ Email Service** — Contact form submissions

---

## OWASP Top 10 Assessment

| # | Threat | Risk Level | Applicable? | Mitigation |
|---|---|---|---|---|
| A01 | **Broken Access Control** | Medium | YES — CMS admin access | GitHub OAuth; only authorized GitHub users can access /admin; Nginx restricts /admin path if needed |
| A02 | **Cryptographic Failures** | Low | YES — data in transit | HTTPS enforced (Let's Encrypt); HSTS header; no sensitive data at rest |
| A03 | **Injection** | Low | YES — Markdown rendering | Content from trusted sources (Git repo); Markdown parsed with safe CommonMark; output escaped in Blade with `{{ }}`; no SQL (no database) |
| A04 | **Insecure Design** | Low | YES — architecture | File-based design minimizes attack surface; no database to compromise; no user accounts to breach |
| A05 | **Security Misconfiguration** | Medium | YES — server config | Security headers configured; directory listing disabled; PHP error display off in production; `.env` file protected |
| A06 | **Vulnerable Components** | Medium | YES — dependencies | Dependabot monitoring; `composer audit` and `npm audit` in CI; regular updates |
| A07 | **Auth Failures** | Low | YES — CMS auth | GitHub OAuth (managed by GitHub); session tokens managed by GitHub; no custom auth code |
| A08 | **Data Integrity Failures** | Medium | YES — Git content | Content signed by Git commits; CI validates content on merge; Decap CMS uses authenticated GitHub API |
| A09 | **Logging & Monitoring** | Medium | YES — operational | Nginx access/error logs; Laravel logs; error tracking (Sentry/Flare); log rotation configured |
| A10 | **SSRF** | Low | Partially | No outbound requests from user input; contact form sends email (not HTTP); Laravel does not fetch external URLs based on user input |

---

## Specific Threats and Mitigations

### T1: Unauthorized CMS Access
- **Threat**: Unauthorized person accesses `/admin` and modifies content
- **Likelihood**: Low
- **Impact**: High (defacement, malicious content)
- **Mitigation**: 
  - GitHub OAuth requires valid GitHub account with repository access
  - Decap CMS `accept_roles` configuration limits access
  - Can add Nginx IP whitelist for `/admin` path
  - Git commit history provides full audit trail

### T2: Content Injection via CMS
- **Threat**: Compromised CMS account injects malicious scripts into content
- **Likelihood**: Low
- **Impact**: High (XSS, malware distribution)
- **Mitigation**:
  - Blade `{{ }}` escapes all output by default
  - Markdown rendered with safe CommonMark (no raw HTML by default)
  - Content Security Policy (CSP) headers prevent inline script execution
  - Git history enables quick rollback
  - Editorial workflow (optional) requires approval before publish

### T3: Contact Form Abuse
- **Threat**: Spam bots submit contact form repeatedly
- **Likelihood**: High
- **Impact**: Low-Medium (email flooding, resource consumption)
- **Mitigation**:
  - Honeypot field (hidden field that bots fill)
  - Rate limiting on form submission endpoint (Laravel throttle middleware)
  - CSRF token required (Laravel built-in)
  - CAPTCHA if spam becomes problematic (hCaptcha, privacy-friendly)
  - Email validation on server side

### T4: DDoS / Traffic Flooding
- **Threat**: Attacker floods server with requests
- **Likelihood**: Low (marketing site, low-profile target)
- **Impact**: Medium (downtime, cost)
- **Mitigation**:
  - Nginx rate limiting
  - Cloudflare free tier (optional) for DDoS protection
  - Response caching reduces PHP load
  - VPS provider's network-level protection

### T5: Server Compromise
- **Threat**: Attacker gains server access via SSH or web vulnerability
- **Likelihood**: Low
- **Impact**: Critical
- **Mitigation**:
  - SSH key-only authentication (no passwords)
  - Fail2ban for brute-force protection
  - Unattended security upgrades
  - Firewall (UFW) with minimal open ports (80, 443, 22)
  - Non-root application user
  - Regular backups
  - File integrity monitoring

### T6: Content Loss
- **Threat**: Content accidentally deleted or corrupted
- **Likelihood**: Low
- **Impact**: Medium
- **Mitigation**:
  - Git provides full version history — any version can be restored
  - GitHub repository backed up (GitHub's infrastructure)
  - Optional: periodic Git bundle backup to separate storage
  - Decap CMS editorial workflow (optional) prevents accidental publishes

### T7: Man-in-the-Middle (MITM)
- **Threat**: Attacker intercepts traffic between user and server
- **Likelihood**: Low
- **Impact**: Medium (data interception)
- **Mitigation**:
  - HTTPS enforced via Let's Encrypt
  - HSTS header prevents downgrade attacks
  - No sensitive data transmitted (contact form only)

### T8: Supply Chain Attack
- **Threat**: Compromised npm/Composer package introduces malicious code
- **Likelihood**: Low
- **Impact**: High
- **Mitigation**:
  - Dependabot monitors for known vulnerabilities
  - Lock files (`composer.lock`, `package-lock.json`) pin exact versions
  - Minimal dependency count (reduce attack surface)
  - SBOM generation on each release
  - Review new dependencies before adding

---

## Security Headers Configuration

```nginx
# To be configured in Nginx
X-Content-Type-Options: nosniff
X-Frame-Options: SAMEORIGIN
X-XSS-Protection: 1; mode=block
Referrer-Policy: strict-origin-when-cross-origin
Permissions-Policy: camera=(), microphone=(), geolocation=()
Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'; img-src 'self' data:; font-src 'self';
Strict-Transport-Security: max-age=31536000; includeSubDomains
```

---

## Security Checklist (Pre-Launch)

- [ ] HTTPS enforced, HTTP redirects to HTTPS
- [ ] Security headers configured (all listed above)
- [ ] CSRF protection on all forms
- [ ] Rate limiting on contact form
- [ ] Honeypot on contact form
- [ ] No sensitive data in source code (`.env` in `.gitignore`)
- [ ] Directory listing disabled in Nginx
- [ ] PHP error display disabled in production
- [ ] SSH key-only authentication
- [ ] Firewall configured (UFW)
- [ ] Fail2ban active
- [ ] Automatic security updates enabled
- [ ] Backups configured
- [ ] Secret scanning passing (GitLeaks)
- [ ] Dependency audit passing (`composer audit`, `npm audit`)
- [ ] OWASP ZAP baseline scan passing
- [ ] CSP header tested and working
- [ ] No mixed content (HTTP resources on HTTPS page)

---

*Document Version: 1.0*  
*Date: 2026-08-11*
