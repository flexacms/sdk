import { createClient, DEFAULT_URL } from '@flexacms/client';
import type {
    ContentSchema,
    FlexaClient,
    FlexaClientOptions,
} from '@flexacms/client';

export * from '@flexacms/client';

/**
 * The parts of SvelteKit's `handle` hook this package uses. Declared here so
 * the package does not depend on a specific SvelteKit version.
 */
interface HandleInput<TEvent extends { locals: object; fetch: typeof fetch }> {
    event: TEvent;
    resolve: (event: TEvent) => Response | Promise<Response>;
}

/**
 * A server `handle` hook that puts a Flexa client on `event.locals.flexa`, so
 * every `load` function and form action can read content with the private key.
 *
 *     // src/hooks.server.ts
 *     import { env } from '$env/dynamic/private';
 *     export const handle = createFlexaHandle({ url: env.FLEXA_URL, apiKey: env.FLEXA_API_KEY });
 *
 * Combine it with other hooks using `sequence()` from `@sveltejs/kit/hooks`.
 * `url` falls back to FLEXA_URL, then https://flexacms.com. `apiKey` falls back to FLEXA_API_KEY.
 */
export function createFlexaHandle<TSchema extends object = ContentSchema>(
    options: Partial<FlexaClientOptions> = {},
) {
    const url = options.url || process.env.FLEXA_URL || DEFAULT_URL;
    const apiKey = options.apiKey ?? process.env.FLEXA_API_KEY;

    if (!apiKey) {
        throw new Error(
            'Flexa: pass "apiKey" (e.g. from $env/dynamic/private) or set FLEXA_API_KEY.',
        );
    }

    return async function handle<
        TEvent extends { locals: object; fetch: typeof fetch },
    >({ event, resolve }: HandleInput<TEvent>) {
        (event.locals as { flexa?: FlexaClient<TSchema> }).flexa =
            createClient<TSchema>({
                ...options,
                url,
                apiKey,
                fetch: options.fetch ?? event.fetch,
            });

        return resolve(event);
    };
}
