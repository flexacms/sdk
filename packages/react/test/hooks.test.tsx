// @vitest-environment jsdom
import { createClient, type Entry } from '@flexacms/client';
import {
    act,
    render,
    renderHook,
    screen,
    waitFor,
} from '@testing-library/react';
import type { ReactNode } from 'react';
import { describe, expect, it, vi } from 'vitest';
import {
    createFlexaHooks,
    FlexaProvider,
    useEntries,
    useEntry,
    useFlexa,
} from '../src';

const entry = (id: number, title: string): Entry => ({
    id,
    entity_id: 1,
    entity_slug: 'posts',
    title,
    slug: `post-${id}`,
    status: 'published',
    order: null,
    categories: [],
    values: { headline: { en: title } },
    created_at: null,
    updated_at: null,
    deleted_at: null,
});

function setup(handler: (url: string) => Response | Promise<Response>) {
    const fetch = vi.fn(async (url: string | URL | Request) =>
        handler(url as string),
    );
    const client = createClient({
        url: 'https://cms.test',
        apiKey: 'k',
        fetch: fetch as unknown as typeof globalThis.fetch,
        retries: 0,
    });
    const wrapper = ({ children }: { children: ReactNode }) => (
        <FlexaProvider client={client}>{children}</FlexaProvider>
    );

    return { fetch, wrapper };
}

const json = (body: unknown, status = 200) =>
    new Response(JSON.stringify(body), { status });

describe('react hooks', () => {
    it('loads a page of entries', async () => {
        const { wrapper, fetch } = setup(() =>
            json({
                data: [entry(1, 'Hello')],
                meta: { current_page: 1, last_page: 1, total: 1 },
                links: {},
            }),
        );

        const { result } = renderHook(
            () => useEntries('posts', { status: 'published' }),
            { wrapper },
        );

        expect(result.current.isLoading).toBe(true);
        await waitFor(() => expect(result.current.isLoading).toBe(false));
        expect(result.current.data?.data[0]?.title).toBe('Hello');
        expect(fetch.mock.calls[0]?.[0] as string).toBe(
            'https://cms.test/api/v1/posts?status=published',
        );
    });

    it('resolves a missing entry to null and waits for an identifier', async () => {
        const { wrapper, fetch } = setup(() =>
            json({ message: 'Record [nope] not found.' }, 404),
        );

        const waiting = renderHook(() => useEntry('posts', undefined), {
            wrapper,
        });
        expect(waiting.result.current.isLoading).toBe(false);
        expect(fetch).not.toHaveBeenCalled();

        const { result } = renderHook(() => useEntry('posts', 'nope'), {
            wrapper,
        });
        await waitFor(() => expect(result.current.isLoading).toBe(false));
        expect(result.current.data).toBeNull();
        expect(result.current.error).toBeUndefined();
    });

    it('exposes errors and can refetch', async () => {
        let fail = true;
        const { wrapper } = setup(() =>
            fail
                ? json({ message: 'Invalid API key.' }, 401)
                : json({ data: entry(2, 'Back') }),
        );

        const { result } = renderHook(() => useEntry('posts', 2), { wrapper });
        await waitFor(() =>
            expect(result.current.error?.message).toBe('Invalid API key.'),
        );

        fail = false;
        act(() => result.current.refetch());
        await waitFor(() => expect(result.current.data?.title).toBe('Back'));
    });

    it('ignores stale responses when the params change', async () => {
        const { wrapper } = setup(async (url) => {
            if (url.includes('slow')) {
                await new Promise((resolve) => setTimeout(resolve, 50));
            }

            return json({
                data: entry(1, url.includes('slow') ? 'Slow' : 'Fast'),
            });
        });

        const { result, rerender } = renderHook(
            ({ slug }) => useEntry('posts', slug),
            { wrapper, initialProps: { slug: 'slow' } },
        );
        rerender({ slug: 'fast' });

        await waitFor(() => expect(result.current.data?.title).toBe('Fast'));
        await new Promise((resolve) => setTimeout(resolve, 80));
        expect(result.current.data?.title).toBe('Fast');
    });

    it('requires a provider', () => {
        const Component = () => {
            useFlexa();

            return null;
        };

        vi.spyOn(console, 'error').mockImplementation(() => {});
        expect(() => render(<Component />)).toThrow('inside a <FlexaProvider>');
    });

    it('creates hooks typed with a generated schema', async () => {
        type Schema = { posts: { headline: { en?: string | null } } };
        const { useEntry: useTypedEntry } = createFlexaHooks<Schema>();
        const { wrapper } = setup(() => json({ data: entry(3, 'Typed') }));

        function Title() {
            const { data } = useTypedEntry('posts', 3);
            const headline: string | null | undefined =
                data?.values.headline.en;

            return <p>{headline ?? 'loading'}</p>;
        }

        render(<Title />, { wrapper });
        expect(await screen.findByText('Typed')).toBeTruthy();
    });
});
