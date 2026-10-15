import { describe, expect, it, vi } from 'vitest';
import { createClient, FlexaError, localize } from '../src';

type Call = { url: string; init: RequestInit };

function mockFetch(...responses: (Response | Error)[]) {
    const calls: Call[] = [];
    const fetch = vi.fn(
        async (url: string | URL | Request, init?: RequestInit) => {
            calls.push({ url: url as string, init: init ?? {} });
            const next = responses.shift();

            if (!next) {
                throw new Error('Unexpected request');
            }

            if (next instanceof Error) {
                throw next;
            }

            return next;
        },
    );

    return { fetch: fetch as unknown as typeof globalThis.fetch, calls };
}

function json(
    body: unknown,
    status = 200,
    headers: Record<string, string> = {},
): Response {
    return new Response(JSON.stringify(body), {
        status,
        headers: { 'Content-Type': 'application/json', ...headers },
    });
}

const entry = (id: number, values: Record<string, unknown> = {}) => ({
    id,
    entity_id: 1,
    entity_slug: 'posts',
    title: `Post ${id}`,
    slug: `post-${id}`,
    status: 'published',
    order: null,
    categories: [],
    values,
    created_at: null,
    updated_at: null,
    deleted_at: null,
});

const page = (ids: number[], current: number, last: number) => ({
    data: ids.map((id) => entry(id)),
    meta: {
        current_page: current,
        last_page: last,
        per_page: 100,
        total: 0,
        from: null,
        to: null,
        path: '',
    },
    links: { first: null, last: null, prev: null, next: null },
});

describe('createClient', () => {
    it('requires the api key', () => {
        expect(() =>
            createClient({ url: 'https://cms.test', apiKey: '' }),
        ).toThrow('"apiKey"');
    });

    it('defaults the url to flexacms.com', () => {
        expect(createClient({ apiKey: 'key' }).baseUrl).toBe(
            'https://flexacms.com/api/v1',
        );
        expect(createClient({ url: '', apiKey: 'key' }).baseUrl).toBe(
            'https://flexacms.com/api/v1',
        );
    });

    it('sends the api key as a bearer token to the content api', async () => {
        const { fetch, calls } = mockFetch(json({ data: [] }));
        const client = createClient({
            url: 'https://cms.test/',
            apiKey: 'flx_secret',
            fetch,
            headers: { 'X-Site': 'blog' },
        });

        await client.entities();

        expect(calls[0]?.url).toBe('https://cms.test/api/v1/entities');
        expect(calls[0]?.init.headers).toMatchObject({
            Authorization: 'Bearer flx_secret',
            Accept: 'application/json',
            'X-Site': 'blog',
        });
    });

    it('supports a custom api path', async () => {
        const { fetch, calls } = mockFetch(json({ data: [] }));

        await createClient({
            url: 'https://cms.test',
            apiKey: 'k',
            apiPath: 'content/',
            fetch,
        }).entities();

        expect(calls[0]?.url).toBe('https://cms.test/content/entities');
    });
});

describe('entries', () => {
    it('maps list parameters to the api query string', async () => {
        const { fetch, calls } = mockFetch(json(page([1], 1, 1)));
        const client = createClient({
            url: 'https://cms.test',
            apiKey: 'k',
            fetch,
        });

        const result = await client.entity('posts').list({
            status: 'published',
            search: 'hello world',
            category: 3,
            page: 2,
            perPage: 10,
            includeRelations: true,
        });

        expect(result.data[0]?.id).toBe(1);
        expect(calls[0]?.url).toBe(
            'https://cms.test/api/v1/posts?status=published&search=hello+world&category=3&page=2&per_page=10&include=relations',
        );
    });

    it('fetches every page with all()', async () => {
        const { fetch, calls } = mockFetch(
            json(page([1, 2], 1, 2)),
            json(page([3], 2, 2)),
        );

        const entries = await createClient({
            url: 'https://cms.test',
            apiKey: 'k',
            fetch,
        })
            .entity('posts')
            .all({ status: 'published' });

        expect(entries.map((item) => item.id)).toEqual([1, 2, 3]);
        expect(calls.map((call) => call.url)).toEqual([
            'https://cms.test/api/v1/posts?status=published&page=1&per_page=100',
            'https://cms.test/api/v1/posts?status=published&page=2&per_page=100',
        ]);
    });

    it('reads an entry by slug and returns null from find() when it is missing', async () => {
        const { fetch, calls } = mockFetch(
            json({ data: entry(7) }),
            json({ message: 'Record [nope] not found.' }, 404),
        );
        const posts = createClient({
            url: 'https://cms.test',
            apiKey: 'k',
            fetch,
        }).entity('posts');

        expect((await posts.get('post-7', { includeRelations: true })).id).toBe(
            7,
        );
        expect(await posts.find('nope')).toBeNull();
        expect(calls[0]?.url).toBe(
            'https://cms.test/api/v1/posts/post-7?include=relations',
        );
    });

    it('sends writes as json with the right method', async () => {
        const { fetch, calls } = mockFetch(
            json({ data: entry(1) }, 201),
            json({ data: entry(1) }),
            json({ message: 'Record permanently deleted.' }),
            json({ data: entry(1) }),
        );
        const posts = createClient({
            url: 'https://cms.test',
            apiKey: 'k',
            fetch,
        }).entity('posts');

        await posts.create({
            title: 'Hello',
            values: { excerpt: { en: 'Hi' } },
        });
        await posts.update(1, { status: 'archived' });
        await posts.delete(1, { force: true });
        await posts.restore(1);

        expect(calls.map((call) => `${call.init.method} ${call.url}`)).toEqual([
            'POST https://cms.test/api/v1/posts',
            'PATCH https://cms.test/api/v1/posts/1',
            'DELETE https://cms.test/api/v1/posts/1?force=1',
            'POST https://cms.test/api/v1/posts/1/restore',
        ]);
        expect(JSON.parse(calls[0]?.init.body as string)).toEqual({
            title: 'Hello',
            values: { excerpt: { en: 'Hi' } },
        });
        expect(calls[0]?.init.headers).toMatchObject({
            'Content-Type': 'application/json',
        });
    });

    it('merges per-request fetch options, as framework adapters do', async () => {
        const { fetch, calls } = mockFetch(json(page([], 1, 1)));

        await createClient({ url: 'https://cms.test', apiKey: 'k', fetch })
            .entity('posts')
            .list(
                {},
                {
                    init: {
                        cache: 'force-cache',
                        next: { tags: ['flexa:posts'] },
                    },
                },
            );

        expect(calls[0]?.init).toMatchObject({
            cache: 'force-cache',
            next: { tags: ['flexa:posts'] },
        });
    });
});

describe('errors and retries', () => {
    it('throws a FlexaError with validation errors', async () => {
        const { fetch } = mockFetch(
            json(
                {
                    message: 'The title field is required.',
                    errors: { title: ['The title field is required.'] },
                },
                422,
            ),
        );

        const error = await createClient({
            url: 'https://cms.test',
            apiKey: 'k',
            fetch,
        })
            .entity('posts')
            .create({})
            .catch((caught: unknown) => caught);

        expect(error).toBeInstanceOf(FlexaError);
        expect(error).toMatchObject({
            status: 422,
            message: 'The title field is required.',
            isValidationError: true,
        });
        expect((error as FlexaError).errors.title).toEqual([
            'The title field is required.',
        ]);
    });

    it('retries reads after a rate limit, honouring Retry-After', async () => {
        vi.useFakeTimers();
        const { fetch, calls } = mockFetch(
            json({ message: 'Too Many Attempts.' }, 429, {
                'Retry-After': '1',
            }),
            json({ data: [] }),
        );

        const request = createClient({
            url: 'https://cms.test',
            apiKey: 'k',
            fetch,
        }).entities();
        await vi.advanceTimersByTimeAsync(1000);

        await expect(request).resolves.toEqual([]);
        expect(calls).toHaveLength(2);
        vi.useRealTimers();
    });

    it('never retries writes', async () => {
        const { fetch, calls } = mockFetch(
            json({ message: 'Service Unavailable' }, 503),
        );

        await expect(
            createClient({ url: 'https://cms.test', apiKey: 'k', fetch })
                .entity('posts')
                .create({ title: 'x' }),
        ).rejects.toMatchObject({
            status: 503,
        });
        expect(calls).toHaveLength(1);
    });

    it('reports network failures with status 0 after the last attempt', async () => {
        const { fetch, calls } = mockFetch(new TypeError('fetch failed'));

        await expect(
            createClient({
                url: 'https://cms.test',
                apiKey: 'k',
                fetch,
                retries: 0,
            }).entities(),
        ).rejects.toMatchObject({
            status: 0,
            message: 'Flexa request failed: fetch failed',
        });
        expect(calls).toHaveLength(1);
    });
});

describe('localize', () => {
    it('picks the locale, then the fallbacks, then any translation', () => {
        expect(localize({ en: 'Hello', es: 'Hola' }, 'es')).toBe('Hola');
        expect(localize({ en: 'Hello', es: '' }, 'es', ['en'])).toBe('Hello');
        expect(localize({ en: null, es: 'Hola' }, 'fr')).toBe('Hola');
        expect(localize(null, 'en')).toBeNull();
    });

    it('passes plain values through', () => {
        expect(localize('Plain', 'es')).toBe('Plain');
        expect(localize(42, 'es')).toBe(42);
    });
});
