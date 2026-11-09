/**
 * Read-only allowlist for the server proxy: which API paths and query keys a
 * browser may reach through it. Writes, trashed entries, unpublished entries and
 * unknown parameters never pass, whatever abilities the API key has.
 */
const ALLOWED_QUERY = ['search', 'category', 'page', 'per_page'] as const;

export function proxyRequestPath(
    path: string,
    query: Record<string, unknown>,
): string | null {
    const segments = path.split('/').filter(Boolean);

    if (
        segments.length === 0 ||
        segments.length > 2 ||
        segments.some((segment) => segment === '..' || segment === '.')
    ) {
        return null;
    }

    if (segments[0] === 'entities' && segments.length > 1) {
        return null;
    }

    const search = new URLSearchParams();

    // Visitors only ever see published content: drafts and scheduled entries stay private.
    if (segments.length === 1 && segments[0] !== 'entities') {
        search.set('status', 'published');
    }

    for (const key of ALLOWED_QUERY) {
        const value = query[key];

        if (typeof value === 'string' || typeof value === 'number') {
            search.set(key, String(value));
        }
    }

    if (query.include === 'relations' && segments[0] !== 'entities') {
        search.set('include', 'relations');
    }

    const queryString = search.toString();

    return `/${segments.map(encodeURIComponent).join('/')}${queryString ? `?${queryString}` : ''}`;
}

/**
 * Whether a proxied path reads one entry, whose status must then be checked.
 */
export function isEntryPath(path: string): boolean {
    const segments = (path.split('?')[0] ?? '').split('/').filter(Boolean);

    return (
        segments.length === 2 &&
        segments[0] !== 'entities' &&
        segments[1] !== 'schema'
    );
}
