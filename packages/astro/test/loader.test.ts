import { describe, expect, it, vi } from 'vitest';
import { flexaLoader, type FlexaLoaderContext } from '../src';

const entry = (id: number, slug: string | null) => ({
    id,
    entity_id: 1,
    entity_slug: 'posts',
    title: `Post ${id}`,
    slug,
    status: 'published',
    order: null,
    categories: [],
    values: {},
    created_at: null,
    updated_at: `2026-10-0${id}T00:00:00+00:00`,
    deleted_at: null,
});

function context() {
    const items = new Map<
        string,
        { data: Record<string, unknown>; digest?: string | number }
    >();
    const ctx: FlexaLoaderContext = {
        store: {
            set: (item) => {
                items.set(item.id, item);

                return true;
            },
            clear: () => items.clear(),
        },
        logger: { info: vi.fn() },
        parseData: async ({ data }) => data,
        generateDigest: (data) =>
            `digest:${typeof data === 'string' ? data : JSON.stringify(data)}`,
    };

    return { ctx, items };
}

describe('flexaLoader', () => {
    it('loads published entries keyed by slug, falling back to the id', async () => {
        const fetch = vi.fn(
            async (_input: string | URL | Request, _init?: RequestInit) =>
                new Response(
                    JSON.stringify({
                        data: [entry(1, 'hello'), entry(2, null)],
                        meta: { last_page: 1 },
                        links: {},
                    }),
                ),
        );
        const { ctx, items } = context();
        ctx.store.set({ id: 'stale', data: {} });

        await flexaLoader({
            entity: 'posts',
            url: 'https://cms.test',
            apiKey: 'k',
            fetch,
        }).load(ctx);

        expect(fetch.mock.calls[0]?.[0] as string).toBe(
            'https://cms.test/api/v1/posts?status=published&page=1&per_page=100',
        );
        expect([...items.keys()]).toEqual(['hello', '2']);
        expect(items.get('hello')).toMatchObject({
            data: { title: 'Post 1' },
            digest: 'digest:2026-10-01T00:00:00+00:00',
        });
        expect(ctx.logger.info).toHaveBeenCalledWith(
            'Loaded 2 entries from Flexa "posts".',
        );
    });

    it('accepts custom filters and ids', async () => {
        const fetch = vi.fn(
            async (_input: string | URL | Request, _init?: RequestInit) =>
                new Response(
                    JSON.stringify({
                        data: [entry(3, 'x')],
                        meta: { last_page: 1 },
                        links: {},
                    }),
                ),
        );
        const { ctx, items } = context();

        await flexaLoader({
            entity: 'faqs',
            url: 'https://cms.test',
            apiKey: 'k',
            fetch,
            query: { category: 4 },
            id: (item) => `faq-${item.id}`,
        }).load(ctx);

        expect(fetch.mock.calls[0]?.[0] as string).toBe(
            'https://cms.test/api/v1/faqs?category=4&page=1&per_page=100',
        );
        expect([...items.keys()]).toEqual(['faq-3']);
    });

    it('explains missing configuration', async () => {
        vi.stubEnv('FLEXA_URL', '');
        await expect(
            flexaLoader({ entity: 'posts' }).load(context().ctx),
        ).rejects.toThrow('FLEXA_URL and FLEXA_API_KEY');
        vi.unstubAllEnvs();
    });
});
