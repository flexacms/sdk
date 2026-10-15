import { beforeEach, describe, expect, it, vi } from 'vitest';

const revalidateTag = vi.fn();

vi.mock('next/cache', () => ({ revalidateTag }));

const {
    createFlexaClient,
    createRevalidateHandler,
    flexaTags,
    revalidateFlexa,
} = await import('../src');

const json = (body: unknown) => new Response(JSON.stringify(body));

beforeEach(() => {
    revalidateTag.mockClear();
});

describe('flexaTags', () => {
    it('tags the workspace, the entity and the entry', () => {
        expect(flexaTags('/entities')).toEqual(['flexa', 'flexa:entities']);
        expect(flexaTags('/posts?status=published')).toEqual([
            'flexa',
            'flexa:posts',
        ]);
        expect(flexaTags('/posts/schema')).toEqual(['flexa', 'flexa:posts']);
        expect(flexaTags('/posts/hello%20world')).toEqual([
            'flexa',
            'flexa:posts',
            'flexa:posts:hello world',
        ]);
    });
});

describe('createFlexaClient', () => {
    it('caches reads with tags and the configured revalidation', async () => {
        const fetch = vi.fn(
            async (_input: string | URL | Request, _init?: RequestInit) =>
                json({ data: { id: 1 } }),
        );
        const client = createFlexaClient({
            url: 'https://cms.test',
            apiKey: 'k',
            fetch,
            revalidate: 300,
        });

        await client
            .entity('posts')
            .get('hello', {}, { init: { next: { tags: ['home'] } } });

        expect(fetch.mock.calls[0]?.[1]).toMatchObject({
            next: {
                revalidate: 300,
                tags: ['flexa', 'flexa:posts', 'flexa:posts:hello', 'home'],
            },
        });
    });

    it('never caches writes', async () => {
        const fetch = vi.fn(
            async (_input: string | URL | Request, _init?: RequestInit) =>
                json({ data: { id: 1 } }),
        );

        await createFlexaClient({ url: 'https://cms.test', apiKey: 'k', fetch })
            .entity('posts')
            .create({ title: 'x' });

        expect(fetch.mock.calls[0]?.[1]).toMatchObject({
            method: 'POST',
            cache: 'no-store',
        });
        expect(fetch.mock.calls[0]?.[1]).not.toHaveProperty('next');
    });

    it('reads the url and key from the environment', async () => {
        vi.stubEnv('FLEXA_URL', 'https://env.test');
        vi.stubEnv('FLEXA_API_KEY', 'env-key');
        const fetch = vi.fn(
            async (_input: string | URL | Request, _init?: RequestInit) =>
                json({ data: [] }),
        );

        await createFlexaClient({ fetch }).entities();

        expect(fetch.mock.calls[0]?.[0] as string).toBe(
            'https://env.test/api/v1/entities',
        );
        expect(fetch.mock.calls[0]?.[1]).toMatchObject({
            headers: { Authorization: 'Bearer env-key' },
        });
        vi.unstubAllEnvs();
    });

    it('defaults the url to flexacms.com', async () => {
        vi.stubEnv('FLEXA_URL', '');
        vi.stubEnv('FLEXA_API_KEY', 'env-key');
        const fetch = vi.fn(
            async (_input: string | URL | Request, _init?: RequestInit) =>
                json({ data: [] }),
        );

        await createFlexaClient({ fetch }).entities();

        expect(fetch.mock.calls[0]?.[0] as string).toBe(
            'https://flexacms.com/api/v1/entities',
        );
        vi.unstubAllEnvs();
    });

    it('explains missing api key', () => {
        vi.stubEnv('FLEXA_API_KEY', '');
        expect(() => createFlexaClient()).toThrow('FLEXA_API_KEY');
        vi.unstubAllEnvs();
    });
});

describe('revalidation', () => {
    it('revalidates everything, an entity or an entry', () => {
        revalidateFlexa();
        revalidateFlexa('posts');
        revalidateFlexa('posts', 'hello');

        expect(revalidateTag.mock.calls).toEqual([
            ['flexa', 'max'],
            ['flexa:posts', 'max'],
            ['flexa:posts:hello', 'max'],
        ]);
    });

    it('protects the revalidate route with the secret', async () => {
        const POST = createRevalidateHandler({ secret: 's3cret' });

        const denied = await POST(
            new Request('https://site.test', {
                method: 'POST',
                headers: { authorization: 'Bearer nope' },
            }),
        );
        const allowed = await POST(
            new Request('https://site.test', {
                method: 'POST',
                headers: { authorization: 'Bearer s3cret' },
                body: JSON.stringify({ entity: 'posts', entry: 'hello' }),
            }),
        );

        expect(denied.status).toBe(401);
        expect(await allowed.json()).toEqual({
            revalidated: true,
            entity: 'posts',
            entry: 'hello',
        });
        expect(revalidateTag).toHaveBeenCalledTimes(1);
        expect(revalidateTag).toHaveBeenCalledWith('flexa:posts:hello', 'max');
    });

    it('refuses every request when no secret is configured', async () => {
        const POST = createRevalidateHandler({ secret: undefined });

        expect(
            (
                await POST(
                    new Request('https://site.test', {
                        method: 'POST',
                        headers: { authorization: 'Bearer undefined' },
                    }),
                )
            ).status,
        ).toBe(401);
    });
});
