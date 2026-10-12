import { createClient } from '@flexacms/client';
import type { Entry, FlexaClientOptions, ListParams } from '@flexacms/client';

export * from '@flexacms/client';

/**
 * The parts of Astro's loader context this loader uses. Declared here so the
 * package does not depend on a specific Astro version.
 */
export interface FlexaLoaderContext {
    store: {
        set(entry: {
            id: string;
            data: Record<string, unknown>;
            digest?: string | number;
        }): boolean;
        clear(): void;
    };
    logger: { info: (message: string) => void };
    parseData: <TData extends Record<string, unknown>>(props: {
        id: string;
        data: TData;
    }) => Promise<TData>;
    generateDigest: (data: Record<string, unknown> | string) => string;
}

export interface FlexaLoaderOptions extends Partial<FlexaClientOptions> {
    /** Entity slug, e.g. `posts`. */
    entity: string;
    /** Filters for the entries to load. Defaults to published entries only. */
    query?: Omit<ListParams, 'page'>;
    /** Collection id of an entry. Defaults to its slug, or its id when it has none. */
    id?: (entry: Entry) => string;
}

export interface FlexaLoader {
    name: string;
    load(context: FlexaLoaderContext): Promise<void>;
}

/**
 * Load the entries of a Flexa entity into an Astro content collection.
 *
 *     // src/content.config.ts
 *     const posts = defineCollection({
 *         loader: flexaLoader({
 *             entity: 'posts',
 *             url: import.meta.env.FLEXA_URL,
 *             apiKey: import.meta.env.FLEXA_API_KEY,
 *         }),
 *     });
 *
 * `url` and `apiKey` fall back to the FLEXA_URL and FLEXA_API_KEY environment variables.
 */
export function flexaLoader(options: FlexaLoaderOptions): FlexaLoader {
    const {
        entity,
        query = { status: 'published' },
        id = (entry) => entry.slug ?? String(entry.id),
        ...clientOptions
    } = options;

    return {
        name: 'flexa-loader',
        async load({ store, logger, parseData, generateDigest }) {
            const url = clientOptions.url ?? process.env.FLEXA_URL;
            const apiKey = clientOptions.apiKey ?? process.env.FLEXA_API_KEY;

            if (!url || !apiKey) {
                throw new Error(
                    `Flexa loader "${entity}": set FLEXA_URL and FLEXA_API_KEY, or pass "url" and "apiKey".`,
                );
            }

            const entries = await createClient({
                ...clientOptions,
                url,
                apiKey,
            })
                .entity(entity)
                .all(query);

            store.clear();

            for (const entry of entries) {
                const entryId = id(entry);
                const data = await parseData({
                    id: entryId,
                    data: entry as unknown as Record<string, unknown>,
                });

                store.set({
                    id: entryId,
                    data,
                    digest: generateDigest(
                        entry.updated_at ?? JSON.stringify(entry),
                    ),
                });
            }

            logger.info(
                `Loaded ${entries.length} ${entries.length === 1 ? 'entry' : 'entries'} from Flexa "${entity}".`,
            );
        },
    };
}
