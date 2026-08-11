import { siteSettings, seoDefaults } from './content';

/**
 * SEO helpers — Path B.
 *
 * Mirrors the old SeoService: resolves a page's meta values against the
 * site-wide defaults from content/settings/seo.yml so every page always has a
 * complete, non-empty set of tags, plus JSON-LD builders.
 */

export interface Seo {
    title: string;
    description: string;
    image?: string;
    type: string;
    canonical: string;
    robots: string;
    siteName: string;
    twitterHandle?: string;
    googleVerification?: string;
    locale: string;
}

export interface SeoOptions {
    title?: string | null;
    description?: string | null;
    image?: string | null;
    type?: string;
    canonical?: string | null;
    robots?: string;
    locale?: string;
}

function clean(value?: string | null): string | null {
    if (!value) return null;
    const cleaned = value.replace(/\s+/g, ' ').replace(/<[^>]+>/g, '').trim();
    return cleaned === '' ? null : cleaned;
}

function truncate(value: string, limit: number): string {
    if (value.length <= limit) return value;
    return value.slice(0, limit - 1).replace(/\s+\S*$/, '') + '…';
}

export function resolveSeo(url: URL, options: SeoOptions = {}): Seo {
    const settings = siteSettings();
    const defaults = seoDefaults();

    const siteName = settings.siteName || defaults.defaultTitle || 'ElectroServes';

    const pageTitle = clean(options.title);
    const title = pageTitle === null ? defaults.defaultTitle : `${pageTitle} | ${siteName}`;

    const description = truncate(clean(options.description) ?? defaults.defaultDescription, 160);

    const image = clean(options.image) ?? defaults.defaultOgImage;
    const canonical = clean(options.canonical) ?? url.origin + url.pathname;

    return {
        title,
        description,
        image: image || undefined,
        type: options.type ?? 'website',
        canonical,
        robots: options.robots ?? 'index, follow',
        siteName,
        twitterHandle: defaults.twitterHandle,
        googleVerification: defaults.googleVerification,
        locale: options.locale ?? 'en_US',
    };
}

export function toJsonLd(schema: Record<string, unknown>): string {
    try {
        return JSON.stringify(schema)
            .replace(/</g, '\\u003c')
            .replace(/>/g, '\\u003e')
            .replace(/&/g, '\\u0026');
    } catch {
        return '{}';
    }
}

/** Organization / LocalBusiness structured data. */
export function organizationSchema(): Record<string, unknown> {
    const settings = siteSettings();

    const schema: Record<string, unknown> = {
        '@context': 'https://schema.org',
        '@type': 'ElectricalContractor',
        name: settings.siteName,
        url: process.env.SITE_URL || 'https://electroserves.co.tz',
    };

    if (settings.tagline) schema.description = settings.tagline;
    if (settings.phone) schema.telephone = settings.phone;
    if (settings.email) schema.email = settings.email;

    const address: Record<string, string> = { '@type': 'PostalAddress' };
    if (settings.address) address.streetAddress = settings.address;
    if (settings.city) address.addressLocality = settings.city;
    if (settings.country) address.addressCountry = settings.country;
    if (Object.keys(address).length > 1) schema.address = address;

    const sameAs = Object.values(settings.socialLinks).filter(Boolean);
    if (sameAs.length) schema.sameAs = sameAs;

    if (settings.businessHours.length) {
        schema.openingHours = settings.businessHours.map((r) => `${r.day} ${r.hours}`);
    }

    return schema;
}

export function breadcrumbSchema(crumbs: Array<{ label: string; url?: string }>): Record<string, unknown> {
    return {
        '@context': 'https://schema.org',
        '@type': 'BreadcrumbList',
        itemListElement: crumbs.map((crumb, i) => {
            const item: Record<string, unknown> = {
                '@type': 'ListItem',
                position: i + 1,
                name: crumb.label,
            };
            if (crumb.url) item.item = crumb.url;
            return item;
        }),
    };
}
