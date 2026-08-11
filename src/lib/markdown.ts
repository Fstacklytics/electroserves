import { createMarkdownProcessor, type MarkdownProcessor } from '@astrojs/markdown-remark';
import rehypeSanitize from 'rehype-sanitize';

/**
 * Markdown → sanitised HTML — Path B.
 *
 * Replaces the old MarkdownService (league/commonmark). Body content from the
 * CMS is rendered at build time with Astro's markdown engine and passed through
 * rehype-sanitize, which strips raw <script>/<iframe> and other unsafe nodes
 * the same way CommonMark's `html_input: strip` did. So a compromised CMS
 * account cannot inject executable HTML into a page.
 *
 * `renderMarkdown` never throws: a failure logs and returns an empty HTML
 * string, so the page shows its "no body" fallback instead of failing the build.
 */

let processorPromise: Promise<MarkdownProcessor> | null = null;

function getProcessor(): Promise<MarkdownProcessor> {
    if (!processorPromise) {
        processorPromise = createMarkdownProcessor({
            syntaxHighlight: false,
            gfm: true,
            smartypants: false,
            // Default rehype-sanitize schema keeps normal prose (headings,
            // lists, links, code, tables) and removes script/iframe/object and
            // dangerous attributes/URL schemes.
            rehypePlugins: [[rehypeSanitize, {}]],
        });
    }
    return processorPromise;
}

export interface RenderedMarkdown {
    html: string;
    /** h2/h3 headings for an auto-generated table of contents. */
    headings: Array<{ level: number; text: string; id: string }>;
}

export async function renderMarkdown(markdown: string, context = 'markdown'): Promise<RenderedMarkdown> {
    if (!markdown || !markdown.trim()) {
        return { html: '', headings: [] };
    }

    try {
        const processor = await getProcessor();
        const { code, metadata } = await processor.render(markdown);

        const headings = (metadata.headings || [])
            .filter((h) => h.depth === 2 || h.depth === 3)
            .map((h) => ({ level: h.depth, text: h.text, id: h.slug }));

        return { html: code, headings };
    } catch (error) {
        console.error(`[markdown] Failed to render ${context}:`, error);
        return { html: '', headings: [] };
    }
}
