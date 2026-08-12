import { z } from 'zod';

/**
 * Content collection schemas — Path B.
 *
 * These mirror the validation the old PHP DataObjects performed in
 * `fromArray()` (see app/DataObjects/*.php, now removed). Every file read from
 * `content/` at build time is validated against these schemas at the boundary;
 * a file that fails validation is logged and skipped (never a build failure and
 * never a 500 in the browser).
 *
 * Fields follow the Decap CMS collection definitions in
 * public/admin/config.yml so that what the CMS writes always
 * round-trips through these schemas. YAML folder collections there must
 * set extension: yml / format: yml (see docs/NEXT-SESSION-CONTENT-NOT-LIVE.md).
 */

/** An empty string means "not set" (the CMS writes "" for absent images). */
const optionalText = () => z.string().optional();

/**
 * Dates are authored as "YYYY-MM-DD" strings, but gray-matter/YAML parse them
 * into JS Date objects. Accept either and normalise to a string in the loader.
 */
const optionalDate = () => z.union([z.string(), z.date()]).optional();

export const serviceCategorySchema = z.enum([
    'residential',
    'commercial',
    'electronics',
    'emergency',
    'installation',
]);

export const projectCategorySchema = z.enum([
    'residential',
    'commercial',
    'industrial',
    'electronics',
    'installation',
]);

export const heroSlideSchema = z.object({
    heading: z.string().default(''),
    subheading: z.string().default(''),
    image: optionalText(),
    cta_text: z.string().default('Get a Quote'),
    cta_link: z.string().default('/contact'),
    cta_secondary_text: optionalText(),
    cta_secondary_link: optionalText(),
    order: z.number().default(1),
    published: z.boolean().default(true),
});

export const serviceSchema = z.object({
    title: z.string().min(1),
    slug: z.string().min(1),
    icon: z.string().default('bolt'),
    short_description: z.string().default(''),
    image: optionalText(),
    features: z
        .array(z.union([z.object({ feature: z.string() }), z.string()]))
        .default([]),
    price_range: optionalText(),
    category: serviceCategorySchema.default('residential'),
    order: z.number().default(1),
    published: z.boolean().default(true),
    meta_description: optionalText(),
    body: z.string().default(''),
});

export const projectSchema = z.object({
    title: z.string().min(1),
    slug: z.string().min(1),
    category: projectCategorySchema.default('residential'),
    short_description: z.string().default(''),
    featured_image: optionalText(),
    gallery: z
        .array(z.object({ image: z.string().optional(), caption: z.string().optional() }))
        .default([]),
    client_name: optionalText(),
    location: optionalText(),
    completion_date: optionalDate(),
    services_used: z.array(z.string()).default([]),
    featured: z.boolean().default(false),
    published: z.boolean().default(true),
    meta_description: optionalText(),
    body: z.string().default(''),
});

export const blogPostSchema = z.object({
    title: z.string().min(1),
    slug: z.string().min(1),
    author: z.string().default('ElectroServes'),
    date: optionalDate(),
    category: z.string().default('Tips & Advice'),
    tags: z.array(z.string()).default([]),
    excerpt: z.string().default(''),
    featured_image: optionalText(),
    published: z.boolean().default(true),
    meta_description: optionalText(),
    body: z.string().default(''),
});

export const testimonialSchema = z.object({
    client_name: z.string().min(1),
    company: optionalText(),
    rating: z.number().min(1).max(5).default(5),
    quote: z.string().default(''),
    photo: optionalText(),
    service_used: optionalText(),
    order: z.number().default(1),
    published: z.boolean().default(true),
});

export const teamMemberSchema = z.object({
    name: z.string().min(1),
    role: z.string().default(''),
    bio: z.string().default(''),
    photo: optionalText(),
    certifications: z
        .array(
            z.object({
                name: z.string().default(''),
                issuer: z.string().optional(),
                year: z.number().optional(),
            }),
        )
        .default([]),
    email: optionalText(),
    order: z.number().default(1),
    published: z.boolean().default(true),
});

export const faqSchema = z.object({
    question: z.string().min(1),
    answer: z.string().default(''),
    category: z.string().default('General'),
    order: z.number().default(1),
    published: z.boolean().default(true),
});

export const pageSchema = z.object({
    layout: z.string().default('page'),
    title: z.string().min(1),
    slug: z.string().min(1),
    body: z.string().default(''),
    featured_image: optionalText(),
    meta_description: optionalText(),
    updated_at: optionalDate(),
    published: z.boolean().default(true),
});

export const siteSettingsSchema = z.object({
    site_name: z.string().default('ElectroServes'),
    tagline: z.string().default(''),
    phone: z.string().default(''),
    emergency_phone: z.string().default(''),
    email: z.string().default(''),
    address: z.string().default(''),
    city: z.string().default('Dar es Salaam'),
    country: z.string().default('Tanzania'),
    map_embed_url: optionalText(),
    business_hours: z
        .array(z.object({ day: z.string().default(''), hours: z.string().default('') }))
        .default([]),
    social_links: z
        .object({
            facebook: z.string().optional(),
            instagram: z.string().optional(),
            twitter: z.string().optional(),
            linkedin: z.string().optional(),
            youtube: z.string().optional(),
            whatsapp: z.string().optional(),
        })
        .default({}),
    copyright: z.string().default('ElectroServes Tanzania Limited'),
    logo: optionalText(),
    favicon: optionalText(),
    years_in_business: z.number().default(0),
    projects_completed: z.number().default(0),
    happy_clients: z.number().default(0),
    team_size: z.number().default(0),
});

export const seoDefaultsSchema = z.object({
    default_title: z.string().default('ElectroServes'),
    default_description: z.string().default(''),
    default_og_image: optionalText(),
    twitter_handle: optionalText(),
    google_verification: optionalText(),
});

/** Typed collection record, keyed by collection name. */
export type CollectionKey =
    | 'hero'
    | 'services'
    | 'projects'
    | 'blog'
    | 'testimonials'
    | 'team'
    | 'faqs'
    | 'pages';
