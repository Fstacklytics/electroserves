import type { APIRoute } from 'astro';
import { services, projects, blogPosts } from '../lib/content';

export const GET: APIRoute = ({ site }) => {
    const origin = String(site ?? 'https://electroserves.co.tz').replace(/\/$/, '');

    const urls: Array<{ loc: string; lastmod?: string; changefreq?: string; priority?: string }> = [
        { loc: `${origin}/`, changefreq: 'daily', priority: '1.0' },
        { loc: `${origin}/services`, changefreq: 'weekly', priority: '0.9' },
        { loc: `${origin}/projects`, changefreq: 'weekly', priority: '0.8' },
        { loc: `${origin}/blog`, changefreq: 'weekly', priority: '0.8' },
        { loc: `${origin}/about`, changefreq: 'monthly', priority: '0.7' },
        { loc: `${origin}/testimonials`, changefreq: 'monthly', priority: '0.5' },
        { loc: `${origin}/faq`, changefreq: 'monthly', priority: '0.6' },
        { loc: `${origin}/contact`, changefreq: 'monthly', priority: '0.9' },
        { loc: `${origin}/privacy-policy`, changefreq: 'yearly', priority: '0.3' },
        { loc: `${origin}/terms`, changefreq: 'yearly', priority: '0.3' },
    ];

    for (const service of services()) {
        urls.push({ loc: `${origin}/services/${service.slug}`, changefreq: 'monthly', priority: '0.8' });
    }
    for (const project of projects()) {
        urls.push({
            loc: `${origin}/projects/${project.slug}`,
            lastmod: project.completionDateIso() ?? undefined,
            changefreq: 'yearly',
            priority: '0.7',
        });
    }
    for (const post of blogPosts()) {
        urls.push({
            loc: `${origin}/blog/${post.slug}`,
            lastmod: post.dateIso() ?? undefined,
            changefreq: 'monthly',
            priority: '0.7',
        });
    }

    const body = `<?xml version="1.0" encoding="UTF-8"?>\n<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">\n${urls
        .map(
            (u) =>
                `  <url>\n    <loc>${u.loc}</loc>${u.lastmod ? `\n    <lastmod>${u.lastmod}</lastmod>` : ''}${u.changefreq ? `\n    <changefreq>${u.changefreq}</changefreq>` : ''}${u.priority ? `\n    <priority>${u.priority}</priority>` : ''}\n  </url>`,
        )
        .join('\n')}\n</urlset>\n`;

    return new Response(body, {
        headers: {
            'Content-Type': 'application/xml; charset=utf-8',
        },
    });
};
