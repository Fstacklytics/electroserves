# User Flows Document — ElectroServes

---

## Actors

| Actor | Description |
|---|---|
| **Visitor (Anonymous)** | Any person browsing the website — potential customer, existing customer, or general visitor |
| **Content Administrator** | Authorized ElectroServes staff member who manages website content via Decap CMS |
| **Developer** | Technical team member who maintains code, deploys, and manages infrastructure |

---

## Flow 1: Visitor Discovers Services

```
Landing (Homepage / Google Search)
  ├── Read hero section (value proposition + CTA)
  ├── Browse services overview section
  │     └── Click specific service → Service Detail Page
  │           ├── Read service description
  │           ├── View related projects
  │           └── Click "Request Quote" → Contact Page / Form
  ├── View testimonials/reviews
  ├── View project gallery
  └── Click "Contact Us" → Contact Page
```

---

## Flow 2: Visitor Requests a Service

```
Any Page
  └── Click "Get a Quote" / "Contact Us" CTA
        ├── Fill out contact form
        │     ├── Name
        │     ├── Email
        │     ├── Phone
        │     ├── Service type (dropdown)
        │     ├── Message / description of need
        │     └── Submit
        │           ├── Success: Confirmation message displayed
        │           └── Error: Form re-displayed with error messages
        ├── OR: Click phone number (mobile: tap-to-call)
        ├── OR: Click email (opens email client)
        └── OR: View location on map
```

---

## Flow 3: Visitor Explores the Company

```
Navigation Menu
  ├── About Us Page
  │     ├── Company history and mission
  │     ├── Team members with photos and bios
  │     ├── Certifications and licenses
  │     └── Company values
  ├── Projects / Portfolio Page
  │     ├── Browse project gallery (filterable by category)
  │     ├── Click project → Project detail
  │     │     ├── Before/after photos
  │     │     ├── Project description
  │     │     └── Services used
  │     └── Return to gallery
  └── Blog Page (if enabled)
        ├── Browse posts list
        ├── Click post → Read full article
        └── Navigate by category/tag
```

---

## Flow 4: Visitor Finds Emergency Services

```
Any Page
  ├── See emergency banner/sticky CTA
  │     └── Click → Emergency page or tap-to-call
  ├── Emergency Page
  │     ├── 24/7 availability notice
  │     ├── Large tap-to-call phone button
  │     ├── Emergency services list
  │     └── Response time expectations
  └── Contact Page → Emergency option in form
```

---

## Flow 5: Content Administrator Updates Content

```
Navigate to /admin
  ├── Authenticate via GitHub OAuth
  │     ├── Success → CMS Dashboard
  │     └── Failure → Error message, retry
  ├── CMS Dashboard
  │     ├── Select collection (Pages / Services / Projects / Blog / Testimonials / Team)
  │     │     ├── View existing entries list
  │     │     ├── Click entry → Edit form
  │     │     │     ├── Modify fields (title, body, images, metadata)
  │     │     │     ├── Preview changes
  │     │     │     └── Save → Git commit created
  │     │     ├── Click "New" → Create form
  │     │     │     ├── Fill all fields
  │     │     │     ├── Upload media (images)
  │     │     │     └── Save → Git commit created
  │     │     └── Delete entry → Confirmation → Git commit
  │     └── Media Library
  │           ├── Browse uploaded images
  │           ├── Upload new images
  │           └── Delete unused images
  └── Changes trigger site rebuild (via CI/CD or webhook)
```

---

## Flow 6: Visitor Uses Navigation

```
Header Navigation (Desktop: horizontal menu, Mobile: hamburger)
  ├── Home
  ├── Services
  │     ├── Residential Electrical
  │     ├── Commercial Electrical
  │     ├── Electronics Repair
  │     ├── Installations
  │     └── Emergency Services
  ├── Projects
  ├── About Us
  ├── Blog
  ├── Contact
  └── [Sticky CTA: "Get a Quote"]
```

---

## Flow 7: Visitor with Accessibility Needs

```
Any Page
  ├── Keyboard navigation (Tab through all interactive elements)
  ├── Screen reader announces all content with proper ARIA labels
  ├── Focus indicators visible on all interactive elements
  ├── Skip-to-content link available
  ├── Color contrast meets WCAG AA (4.5:1 for text)
  ├── All images have alt text
  └── Form inputs have associated labels and error announcements
```

---

*Document Version: 1.0*  
*Date: 2026-08-11*
