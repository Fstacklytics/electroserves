import en from '../i18n/en.json';

/**
 * Tiny string lookup over the i18n dictionary (port of lang/en/*.php).
 *
 * Keys use dot notation, e.g. t('services.show.whats_included'). Placeholders
 * use {name} syntax and are substituted with the provided params.
 */

type Dict = Record<string, unknown>;

function lookup(path: string): unknown {
    let node: unknown = en as Dict;
    for (const part of path.split('.')) {
        if (node && typeof node === 'object' && part in (node as Dict)) {
            node = (node as Dict)[part];
        } else {
            return undefined;
        }
    }
    return node;
}

export function t(path: string, params?: Record<string, string | number>): string {
    const raw = lookup(path);
    if (typeof raw !== 'string') {
        console.warn(`[i18n] Missing translation key: ${path}`);
        return path;
    }

    if (!params) return raw;

    return raw.replace(/\{(\w+)\}/g, (match, key) =>
        key in params ? String(params[key]) : match,
    );
}
