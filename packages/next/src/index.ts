import { createClient, DEFAULT_URL } from '@flexacms/client';
import type {
    ContentSchema,
    FlexaClient,
    FlexaClientOptions,
} from '@flexacms/client';
import { revalidateTag } from 'next/cache';

export * from '@flexacms/client';

/** Tag on every cached Flexa response. */
export const FLEXA_TAG = 'flexa';

export interface FlexaNextOptions extends Partial<FlexaClientOptions> {
    /**
     * Seconds before a cached response is refreshed in the background, or
     * `false` to keep it until it is revalidated by tag. Defaults to 60.
     */
    revalidate?: number | false;
}

type NextFetchInit = RequestInit & {
    next?: { revalidate?: number | false; tags?: string[] };
};

/** The URL of any `fetch` input (string, URL or Request). */
function requestUrl(input: string | URL | Request): string {
    if (typeof input === 'string') {
        return input;
    }

    return input instanceof URL ? input.href : input.url;
}

/**
 * Cache tags for an API path: `flexa`, `flexa:<entity>` and, for one entry,
 * `flexa:<entity>:<id or slug>`.
 */
export function flexaTags(path: string): string[] {
    const [entity, entry] = path
        .split('?')[0]!
        .split('/')
        .filter(Boolean)
        .map(decodeURIComponent);
    const tags = [FLEXA_TAG];

    if (entity) {
        tags.push(`${FLEXA_TAG}:${entity}`);
    }

    if (entity && entry && entry !== 'schema') {
        tags.push(`${FLEXA_TAG}:${entity}:${entry}`);
    }

    return tags;
}

/**
 * A Flexa client for Server Components, Route Handlers and Server Actions.
 *
 * Reads go through Next's data cache, tagged per entity and entry so they can
 * be refreshed with `revalidateFlexa()`; writes are never cached. The URL and
 * URL defaults to `FLEXA_URL` or `https://flexacms.com`; the API key defaults to
 * the `FLEXA_API_KEY` environment variable.
 * Only use it on the server: the API key must not reach the browser.
 */
export function createFlexaClient<TSchema extends object = ContentSchema>(
    options: FlexaNextOptions = {},
): FlexaClient<TSchema> {
    const { revalidate = 60, ...clientOptions } = options;
    const url = clientOptions.url || process.env.FLEXA_URL || DEFAULT_URL;
    const apiKey = clientOptions.apiKey ?? process.env.FLEXA_API_KEY;

    if (!apiKey) {
        throw new Error(
            'Flexa: set the FLEXA_API_KEY environment variable, or pass "apiKey".',
        );
    }

    const baseFetch = clientOptions.fetch ?? globalThis.fetch;
    let client: FlexaClient<TSchema>;

    const cachedFetch = ((
        input: string | URL | Request,
        init: NextFetchInit = {},
    ) => {
        if ((init.method ?? 'GET').toUpperCase() !== 'GET') {
            return baseFetch(input, { ...init, cache: 'no-store' });
        }

        const path = new URL(requestUrl(input)).pathname.slice(
            new URL(client.baseUrl).pathname.length,
        );

        return baseFetch(input, {
            ...init,
            next: {
                revalidate,
                ...init.next,
                tags: [...flexaTags(path), ...(init.next?.tags ?? [])],
            },
        } as RequestInit);
    }) as typeof fetch;

    client = createClient<TSchema>({
        ...clientOptions,
        url,
        apiKey,
        fetch: cachedFetch,
    });

    return client;
}

/**
 * Refresh cached Flexa content: everything, one entity, or one entry. Call it
 * from a Server Action or Route Handler after content changes.
 */
export function revalidateFlexa(
    entity?: string,
    idOrSlug?: number | string,
): void {
    const tag =
        entity === undefined
            ? FLEXA_TAG
            : idOrSlug === undefined
              ? `${FLEXA_TAG}:${entity}`
              : `${FLEXA_TAG}:${entity}:${idOrSlug}`;

    revalidateTag(tag, 'max');
}

/**
 * A `POST` Route Handler that revalidates Flexa content, for webhooks or
 * deploy hooks. Send `{ "entity": "posts", "entry": "hello-world" }` (both
 * optional) with the header `Authorization: Bearer <secret>`.
 *
 *     // app/api/flexa/revalidate/route.ts
 *     export const POST = createRevalidateHandler({ secret: process.env.FLEXA_REVALIDATE_SECRET });
 */
export function createRevalidateHandler(options: {
    secret: string | undefined;
}) {
    return async function POST(request: Request): Promise<Response> {
        if (
            !options.secret ||
            request.headers.get('authorization') !== `Bearer ${options.secret}`
        ) {
            return Response.json({ message: 'Unauthorized.' }, { status: 401 });
        }

        let body: { entity?: unknown; entry?: unknown } = {};

        try {
            body = (await request.json()) as typeof body;
        } catch {
            // An empty body revalidates everything.
        }

        const entity =
            typeof body.entity === 'string' && body.entity !== ''
                ? body.entity
                : undefined;
        const entry =
            typeof body.entry === 'string' || typeof body.entry === 'number'
                ? body.entry
                : undefined;

        revalidateFlexa(entity, entity === undefined ? undefined : entry);

        return Response.json({
            revalidated: true,
            entity: entity ?? null,
            entry: entity === undefined ? null : (entry ?? null),
        });
    };
}
