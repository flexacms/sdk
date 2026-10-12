/**
 * Read-only allowlist for the server proxy: which API paths and query keys a
 * browser may reach through it. Writes, trashed entries and unknown parameters
 * never pass, whatever abilities the API key has.
 */
const ALLOWED_QUERY = [
    'status',
    'search',
    'category',
    'page',
    'per_page',
] as const;

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
