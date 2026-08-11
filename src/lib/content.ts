import matter from 'gray-matter';
import YAML from 'yaml';
import {
    serviceSchema,
    projectSchema,
    blogPostSchema,
    testimonialSchema,
    teamMemberSchema,
    faqSchema,
    pageSchema,
    heroSlideSchema,
    siteSettingsSchema,
    seoDefaultsSchema,
    serviceCategorySchema,
    projectCategorySchema,
    type CollectionKey,
} from '../content/config';

/**
 * Build-time content loading — Path B.
 *
 * Replaces the old request-time ContentService. All content is read from the
 * `content/` directory and parsed once, during the Astro build, using Vite's
 * `import.meta.glob`. Every file is validated against the zod schemas in
 * src/content/config.ts at the boundary. A file that is missing or fails
 * validation is logged and skipped — the page renders its fallback empty state
 * rather than failing the build or returning a 500.
 *
 * `import.meta.glob` patterns must be statically analyzable string literals,
 * so each collection has its own explicit call below.
 */

export interface ServiceCategory {
    key: string;
    label: string;
}

/** Human-readable category labels (mirrors config/electroserves.php). */
export const SERVICE_CATEGORIES: ServiceCategory[] = [
    { key: 'residential', label: 'Residential' },
    { key: 'commercial', label: 'Commercial' },
    { key: 'electronics', label: 'Electronics' },
    { key: 'emergency', label: 'Emergency' },
    { key: 'installation', label: 'Installation' },
];

export const PROJECT_CATEGORIES: ServiceCategory[] = [
    { key: 'residential', label: 'Residential' },
    { key: 'commercial', label: 'Commercial' },
    { key: 'industrial', label: 'Industrial' },
    { key: 'electronics', label: 'Electronics' },
    { key: 'installation', label: 'Installation' },
];

export const FAQ_CATEGORIES = ['General', 'Services', 'Pricing', 'Emergency', 'Warranty'];
export const BLOG_CATEGORIES = [
    'Tips & Advice',
    'Industry News',
    'Company Updates',
    'Project Spotlights',
    'Safety',
];

// ---------------------------------------------------------------------------
// Logging (build-time). No silent failures.
// ---------------------------------------------------------------------------

function log(level: 'warn' | 'error', message: string): void {
    const fn = level === 'error' ? console.error : console.warn;
    fn(`[content:${level}] ${message}`);
}

// ---------------------------------------------------------------------------
// Small helpers
// ---------------------------------------------------------------------------

/** Parse a "YYYY-MM-DD" string or a JS Date into a local Date, or null. */
export function parseDate(value?: string | Date): Date | null {
    if (!value) return null;
    if (value instanceof Date) {
        return Number.isNaN(value.getTime()) ? null : value;
    }
    const match = /^(\d{4})-(\d{2})-(\d{2})$/.exec(value.trim());
    if (!match) return null;
    const [, y, m, d] = match;
    const date = new Date(Number(y), Number(m) - 1, Number(d));
    return Number.isNaN(date.getTime()) ? null : date;
}

const MONTHS = [
    'January', 'February', 'March', 'April', 'May', 'June',
    'July', 'August', 'September', 'October', 'November', 'December',
];

/** e.g. "22 August 2025" (blog style). */
export function formatLongDate(date: Date | null): string | null {
    if (!date) return null;
    return `${date.getDate()} ${MONTHS[date.getMonth()]} ${date.getFullYear()}`;
}

/** e.g. "August 2025" (project style). */
export function formatMonthYear(date: Date | null): string | null {
    if (!date) return null;
    return `${MONTHS[date.getMonth()]} ${date.getFullYear()}`;
}

export function isoDate(date: Date | null): string | null {
    if (!date) return null;
    return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`;
}

/** Reading time in whole minutes (~200 words/min), minimum 1. */
export function readingTimeMinutes(body: string): number {
    const words = (body.trim().match(/\S+/g) || []).length;
    return Math.max(1, Math.ceil(words / 200));
}

export function initials(name: string): string {
    const parts = (name || '').trim().split(/\s+/);
    let out = '';
    for (const part of parts) {
        const first = (part || '').charAt(0).toUpperCase();
        if (first) out += first;
        if (out.length >= 2) break;
    }
    return out || '?';
}

/** Reduce a phone number to a tel:-safe string, or null when empty. */
function telHref(number?: string): string | null {
    const cleaned = (number || '').replace(/[^0-9+]/g, '');
    return cleaned === '' ? null : `tel:${cleaned}`;
}

function slugifyHeading(text: string): string {
    const slug = (text || '')
        .toLowerCase()
        .replace(/['’`]/g, '')
        .replace(/[^\p{L}\p{N}]+/gu, '-')
        .replace(/^-+|-+$/g, '');
    return slug === '' ? 'section' : slug;
}

/** Normalise a `features: [{ feature: '…' }]` list to plain strings. */
function toFeatureStrings(features: Array<{ feature?: string } | string>): string[] {
    return features.map((f) => (typeof f === 'string' ? f : f.feature || '')).filter(Boolean);
}

// ---------------------------------------------------------------------------
// Public content types
// ---------------------------------------------------------------------------

export interface SiteSettings {
    siteName: string;
    tagline: string;
    phone: string;
    emergencyPhone: string;
    email: string;
    address: string;
    city: string;
    country: string;
    mapEmbedUrl?: string;
    businessHours: Array<{ day: string; hours: string }>;
    socialLinks: Record<string, string>;
    copyright: string;
    logo?: string;
    favicon?: string;
    yearsInBusiness: number;
    projectsCompleted: number;
    happyClients: number;
    teamSize: number;
    phoneHref: () => string | null;
    emergencyPhoneHref: () => string | null;
    fullAddress: () => string;
    hasSocialLinks: () => boolean;
    hasBusinessHours: () => boolean;
    hasStats: () => boolean;
}

export interface SeoDefaults {
    defaultTitle: string;
    defaultDescription: string;
    defaultOgImage?: string;
    twitterHandle?: string;
    googleVerification?: string;
}

export interface HeroSlide {
    heading: string;
    subheading: string;
    image?: string;
    ctaText: string;
    ctaLink: string;
    hasSecondaryCta: () => boolean;
    ctaSecondaryText?: string;
    ctaSecondaryLink?: string;
}

export interface Service {
    title: string;
    slug: string;
    icon: string;
    shortDescription: string;
    image?: string;
    features: string[];
    priceRange?: string;
    category: string;
    categoryLabel: string;
    metaDescription?: string;
    body: string;
    hasFeatures: () => boolean;
}

export interface Project {
    title: string;
    slug: string;
    category: string;
    categoryLabel: string;
    shortDescription: string;
    featuredImage?: string;
    gallery: Array<{ image?: string; caption?: string }>;
    clientName?: string;
    location?: string;
    completionDate: Date | null;
    completionDateIso: () => string | null;
    formattedCompletionDate: () => string | null;
    servicesUsed: string[];
    featured: boolean;
    metaDescription?: string;
    body: string;
}

export interface BlogPost {
    title: string;
    slug: string;
    author: string;
    date: Date | null;
    dateIso: () => string | null;
    formattedDate: () => string | null;
    category: string;
    tags: string[];
    excerpt: string;
    featuredImage?: string;
    metaDescription?: string;
    body: string;
    readingTimeMinutes: () => number;
    hasTags: () => boolean;
}

export interface Testimonial {
    clientName: string;
    company?: string;
    rating: number;
    quote: string;
    photo?: string;
    serviceUsed?: string;
    initials: () => string;
    hasPhoto: () => boolean;
    ratingLabel: () => string;
}

export interface TeamMember {
    name: string;
    role: string;
    bio: string;
    photo?: string;
    certifications: Array<{ name: string; issuer?: string; year?: number }>;
    email?: string;
    initials: () => string;
}

export interface Faq {
    question: string;
    answer: string;
    category: string;
}

export interface StaticPage {
    title: string;
    slug: string;
    body: string;
    featuredImage?: string;
    metaDescription?: string;
    updatedAt: Date | null;
    updatedAtIso: () => string | null;
    formattedUpdatedAt: () => string | null;
}

// ---------------------------------------------------------------------------
// Loaders
// ---------------------------------------------------------------------------

let settingsCache: SiteSettings | null = null;
let seoCache: SeoDefaults | null = null;

export function siteSettings(): SiteSettings {
    if (settingsCache) return settingsCache;

    const files = import.meta.glob('../../content/settings/site.yml', {
        query: '?raw',
        import: 'default',
        eager: true,
    });

    let data: Record<string, unknown> = {};
    const path = Object.keys(files)[0];
    if (path) {
        const parsed = YAML.parse(files[path] as string) || {};
        const result = siteSettingsSchema.safeParse(parsed);
        if (result.success) {
            data = result.data;
        } else {
            log('error', `site settings failed validation (${path}): ${result.error.message}`);
        }
    } else {
        log('warn', 'content/settings/site.yml is missing — using fallback settings.');
    }

    const socialLinks: Record<string, string> = {};
    for (const [key, value] of Object.entries((data as any).social_links || {})) {
        if (typeof value === 'string' && value !== '') socialLinks[key] = value;
    }

    const settings: SiteSettings = {
        siteName: data.site_name || '',
        tagline: data.tagline || '',
        phone: data.phone || '',
        emergencyPhone: data.emergency_phone || '',
        email: data.email || '',
        address: data.address || '',
        city: data.city || '',
        country: data.country || '',
        mapEmbedUrl: data.map_embed_url || undefined,
        businessHours: (data.business_hours || []).map((row: any) => ({
            day: row?.day || '',
            hours: row?.hours || '',
        })),
        socialLinks,
        copyright: data.copyright || '',
        logo: data.logo || undefined,
        favicon: data.favicon || undefined,
        yearsInBusiness: data.years_in_business || 0,
        projectsCompleted: data.projects_completed || 0,
        happyClients: data.happy_clients || 0,
        teamSize: data.team_size || 0,
        phoneHref: () => telHref(settings.phone),
        emergencyPhoneHref: () => telHref(settings.emergencyPhone),
        fullAddress: () =>
            [settings.address, settings.city, settings.country].filter(Boolean).join(', '),
        hasSocialLinks: () => settings.socialLinks && Object.keys(settings.socialLinks).length > 0,
        hasBusinessHours: () => settings.businessHours.length > 0,
        hasStats: () =>
            settings.yearsInBusiness > 0 ||
            settings.projectsCompleted > 0 ||
            settings.happyClients > 0 ||
            settings.teamSize > 0,
    };

    settingsCache = settings;
    return settings;
}

export function seoDefaults(): SeoDefaults {
    if (seoCache) return seoCache;

    const files = import.meta.glob('../../content/settings/seo.yml', {
        query: '?raw',
        import: 'default',
        eager: true,
    });

    let data: any = {};
    const path = Object.keys(files)[0];
    if (path) {
        const parsed = YAML.parse(files[path] as string) || {};
        const result = seoDefaultsSchema.safeParse(parsed);
        if (result.success) {
            data = result.data;
        } else {
            log('error', `SEO defaults failed validation (${path}): ${result.error.message}`);
        }
    } else {
        log('warn', 'content/settings/seo.yml is missing — using fallback SEO defaults.');
    }

    seoCache = {
        defaultTitle: data.default_title || 'ElectroServes',
        defaultDescription: data.default_description || '',
        defaultOgImage: data.default_og_image || undefined,
        twitterHandle: data.twitter_handle || undefined,
        googleVerification: data.google_verification || undefined,
    };

    return seoCache;
}

function loadMarkdownCollection<T>(files: Record<string, string>, schema: any, map: (data: any) => T, sort?: (a: T, b: T) => number, name: string): T[] {
    const items: T[] = [];

    for (const [path, raw] of Object.entries(files)) {
        let parsed: any;
        try {
            parsed = matter(raw as string);
        } catch (error) {
            log('error', `${name}: could not parse frontmatter in ${path} — ${String(error)}`);
            continue;
        }

        const result = schema.safeParse({ ...parsed.data, body: parsed.content });
        if (!result.success) {
            log('error', `${name}: ${path} failed validation — ${result.error.message}`);
            continue;
        }

        if (result.data.published === false) continue;
        items.push(map(result.data));
    }

    if (sort) items.sort(sort);
    return items;
}

function loadYamlCollection<T>(files: Record<string, string>, schema: any, map: (data: any) => T, sort?: (a: T, b: T) => number, name: string): T[] {
    const items: T[] = [];

    for (const [path, raw] of Object.entries(files)) {
        let parsed: any;
        try {
            parsed = YAML.parse(raw as string) || {};
        } catch (error) {
            log('error', `${name}: could not parse YAML in ${path} — ${String(error)}`);
            continue;
        }

        const result = schema.safeParse(parsed);
        if (!result.success) {
            log('error', `${name}: ${path} failed validation — ${result.error.message}`);
            continue;
        }

        if (result.data.published === false) continue;
        items.push(map(result.data));
    }

    if (sort) items.sort(sort);
    return items;
}

const byOrder = (a: any, b: any) => (a.order ?? 0) - (b.order ?? 0);

export function heroSlides(): HeroSlide[] {
    const files = import.meta.glob('../../content/hero/*.yml', {
        query: '?raw',
        import: 'default',
        eager: true,
    });

    return loadYamlCollection<HeroSlide>(files, heroSlideSchema, (d) => {
        const slide: HeroSlide = {
            heading: d.heading,
            subheading: d.subheading,
            image: d.image || undefined,
            ctaText: d.cta_text,
            ctaLink: d.cta_link,
            hasSecondaryCta: () => Boolean(d.cta_secondary_text && d.cta_secondary_link),
            ctaSecondaryText: d.cta_secondary_text || undefined,
            ctaSecondaryLink: d.cta_secondary_link || undefined,
        };
        return slide;
    }, byOrder, 'hero');
}

export function services(): Service[] {
    const files = import.meta.glob('../../content/services/*.md', {
        query: '?raw',
        import: 'default',
        eager: true,
    });

    return loadMarkdownCollection<Service>(files, serviceSchema, (d) => {
        const categoryKey = serviceCategorySchema.parse(d.category || 'residential');
        const label = SERVICE_CATEGORIES.find((c) => c.key === categoryKey)?.label || categoryKey;
        return {
            title: d.title,
            slug: d.slug,
            icon: d.icon,
            shortDescription: d.short_description,
            image: d.image || undefined,
            features: toFeatureStrings(d.features || []),
            priceRange: d.price_range || undefined,
            category: categoryKey,
            categoryLabel: label,
            metaDescription: d.meta_description || undefined,
            body: d.body || '',
            hasFeatures: () => toFeatureStrings(d.features || []).length > 0,
        };
    }, byOrder, 'services');
}

export function projects(): Project[] {
    const files = import.meta.glob('../../content/projects/*.md', {
        query: '?raw',
        import: 'default',
        eager: true,
    });

    return loadMarkdownCollection<Project>(files, projectSchema, (d) => {
        const categoryKey = projectCategorySchema.parse(d.category || 'residential');
        const label = PROJECT_CATEGORIES.find((c) => c.key === categoryKey)?.label || categoryKey;
        const completionDate = parseDate(d.completion_date);
        return {
            title: d.title,
            slug: d.slug,
            category: categoryKey,
            categoryLabel: label,
            shortDescription: d.short_description,
            featuredImage: d.featured_image || undefined,
            gallery: d.gallery || [],
            clientName: d.client_name || undefined,
            location: d.location || undefined,
            completionDate,
            completionDateIso: () => isoDate(completionDate),
            formattedCompletionDate: () => formatMonthYear(completionDate),
            servicesUsed: d.services_used || [],
            featured: d.featured,
            metaDescription: d.meta_description || undefined,
            body: d.body || '',
        };
    }, (a, b) => (b.completionDate?.getTime() || 0) - (a.completionDate?.getTime() || 0), 'projects');
}

export function blogPosts(): BlogPost[] {
    const files = import.meta.glob('../../content/blog/*.md', {
        query: '?raw',
        import: 'default',
        eager: true,
    });

    return loadMarkdownCollection<BlogPost>(files, blogPostSchema, (d) => {
        const date = parseDate(d.date);
        return {
            title: d.title,
            slug: d.slug,
            author: d.author,
            date,
            dateIso: () => isoDate(date),
            formattedDate: () => formatLongDate(date),
            category: d.category,
            tags: d.tags || [],
            excerpt: d.excerpt,
            featuredImage: d.featured_image || undefined,
            metaDescription: d.meta_description || undefined,
            body: d.body || '',
            readingTimeMinutes: () => readingTimeMinutes(d.body || ''),
            hasTags: () => (d.tags || []).length > 0,
        };
    }, (a, b) => (b.date?.getTime() || 0) - (a.date?.getTime() || 0), 'blog');
}

export function testimonials(): Testimonial[] {
    const files = import.meta.glob('../../content/testimonials/*.yml', {
        query: '?raw',
        import: 'default',
        eager: true,
    });

    return loadYamlCollection<Testimonial>(files, testimonialSchema, (d) => ({
        clientName: d.client_name,
        company: d.company || undefined,
        rating: d.rating,
        quote: d.quote,
        photo: d.photo || undefined,
        serviceUsed: d.service_used || undefined,
        initials: () => initials(d.client_name),
        hasPhoto: () => Boolean(d.photo),
        ratingLabel: () => `Rated ${d.rating} out of 5`,
    }), byOrder, 'testimonials');
}

export function teamMembers(): TeamMember[] {
    const files = import.meta.glob('../../content/team/*.yml', {
        query: '?raw',
        import: 'default',
        eager: true,
    });

    return loadYamlCollection<TeamMember>(files, teamMemberSchema, (d) => ({
        name: d.name,
        role: d.role,
        bio: d.bio,
        photo: d.photo || undefined,
        certifications: d.certifications || [],
        email: d.email || undefined,
        initials: () => initials(d.name),
    }), byOrder, 'team');
}

export function faqs(): Faq[] {
    const files = import.meta.glob('../../content/faqs/*.yml', {
        query: '?raw',
        import: 'default',
        eager: true,
    });

    return loadYamlCollection<Faq>(files, faqSchema, (d) => ({
        question: d.question,
        answer: d.answer,
        category: d.category,
    }), byOrder, 'faqs');
}

export function staticPages(): StaticPage[] {
    const files = import.meta.glob('../../content/pages/*.md', {
        query: '?raw',
        import: 'default',
        eager: true,
    });

    return loadMarkdownCollection<StaticPage>(files, pageSchema, (d) => {
        const updatedAt = parseDate(d.updated_at);
        return {
            title: d.title,
            slug: d.slug,
            body: d.body || '',
            featuredImage: d.featured_image || undefined,
            metaDescription: d.meta_description || undefined,
            updatedAt,
            updatedAtIso: () => isoDate(updatedAt),
            formattedUpdatedAt: () => formatLongDate(updatedAt),
        };
    }, undefined, 'pages');
}

export function getService(slug: string): Service | undefined {
    return services().find((s) => s.slug === slug);
}

export function getProject(slug: string): Project | undefined {
    return projects().find((p) => p.slug === slug);
}

export function getBlogPost(slug: string): BlogPost | undefined {
    return blogPosts().find((p) => p.slug === slug);
}

export function getStaticPage(slug: string): StaticPage | undefined {
    return staticPages().find((p) => p.slug === slug);
}

/** Derive a slugified heading id and expose TOC headings for a markdown body. */
export interface TocHeading {
    level: number;
    text: string;
    id: string;
}

export function extractHeadings(body: string): TocHeading[] {
    const headings: TocHeading[] = [];
    const seen = new Map<string, number>();
    let inFence = false;

    for (const line of (body || '').split(/\r?\n/)) {
        if (/^\s*(```|~~~)/.test(line)) {
            inFence = !inFence;
            continue;
        }
        if (inFence) continue;

        const match = /^(#{2,3})\s+(.+?)\s*#*\s*$/.exec(line);
        if (!match) continue;
        const text = match[2].replace(/<[^>]+>/g, '').trim();
        if (!text) continue;

        let id = slugifyHeading(text);
        const count = seen.get(id) || 0;
        seen.set(id, count + 1);
        if (count > 0) id = `${id}-${count}`;

        headings.push({ level: match[1].length, text, id });
    }

    return headings;
}
