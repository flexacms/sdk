import { FlexaError } from './errors';
import type {
    ContentSchema,
    EntitySchema,
    EntitySummary,
    Entry,
    EntryInput,
    EntryValues,
    GetParams,
    ListParams,
    Paginated,
    RequestOptions,
} from './types';

export const DEFAULT_URL = 'https://flexacms.com';

export interface FlexaClientOptions {
    /** Base URL of your Flexa installation. Defaults to `https://flexacms.com`. */
    url?: string;
    /**
     * Workspace API key. Keep it on the server: anyone with the key can do what
     * its abilities allow. Use a Read-only key for public websites.
     */
    apiKey: string;
    /** Path of the content API. Defaults to `/api/v1`. */
    apiPath?: string;
    /** Custom fetch implementation (defaults to the global `fetch`). */
    fetch?: typeof fetch;
    /** Extra headers sent with every request. */
    headers?: Record<string, string>;
    /** Abort requests that take longer than this, in milliseconds. Defaults to 30s. */
    timeout?: number;
    /**
     * How many times to retry reads after a rate limit (429), a gateway error
     * (502/503/504) or a network failure. Writes are never retried. Defaults to 2.
     */
    retries?: number;
}

type ValuesOf<
    TSchema extends object,
    TEntity extends string,
> = TEntity extends keyof TSchema
    ? TSchema[TEntity] extends object
        ? TSchema[TEntity]
        : EntryValues
    : EntryValues;

/** The part of the client entity helpers need: sending requests. */
type RequestSender = Pick<FlexaClient, 'request'>;

const RETRYABLE_STATUSES = new Set([429, 502, 503, 504]);

function sleep(milliseconds: number): Promise<void> {
    return new Promise((resolve) => setTimeout(resolve, milliseconds));
}

/**
 * Content API client for one Flexa workspace (the workspace is set by the API key).
 */
export class FlexaClient<TSchema extends object = ContentSchema> {
    readonly baseUrl: string;

    private readonly options: Required<
        Pick<FlexaClientOptions, 'timeout' | 'retries'>
    > &
        FlexaClientOptions;

    constructor(options: FlexaClientOptions) {
        if (!options.apiKey) {
            throw new TypeError('Flexa: the "apiKey" option is required.');
        }

        const url = options.url || DEFAULT_URL;
        const apiPath = `/${(options.apiPath ?? '/api/v1').replace(/^\/+|\/+$/g, '')}`;

        this.baseUrl = `${url.replace(/\/+$/, '')}${apiPath}`;
        this.options = { timeout: 30_000, retries: 2, ...options };
    }

    /** Entities (content models) the API key can read. */
    async entities(options?: RequestOptions): Promise<EntitySummary[]> {
        const response = await this.request<{ data: EntitySummary[] }>(
            'GET',
            '/entities',
            undefined,
            options,
        );

        return response.data;
    }

    /** Typed access to the entries of one entity. */
    entity<TEntity extends Extract<keyof TSchema, string> | (string & {})>(
        slug: TEntity,
    ): EntityClient<ValuesOf<TSchema, TEntity>> {
        return new EntityClient(this, slug);
    }

    /**
     * Send a request to the content API. Exposed for endpoints the typed helpers
     * do not cover yet; `path` is relative to the API base (e.g. `/posts`).
     */
    async request<T>(
        method: 'GET' | 'POST' | 'PUT' | 'PATCH' | 'DELETE',
        path: string,
        body?: unknown,
        options: RequestOptions = {},
    ): Promise<T> {
        const fetchImplementation = this.options.fetch ?? globalThis.fetch;

        if (typeof fetchImplementation !== 'function') {
            throw new TypeError(
                'Flexa: no fetch implementation available. Pass one with the "fetch" option.',
            );
        }

        const attempts = method === 'GET' ? this.options.retries + 1 : 1;
        let lastError: unknown;

        for (let attempt = 1; attempt <= attempts; attempt++) {
            const signals = [
                AbortSignal.timeout(this.options.timeout),
                options.signal,
            ].filter((signal): signal is AbortSignal => signal !== undefined);

            let response: Response;

            try {
                response = await fetchImplementation(`${this.baseUrl}${path}`, {
                    ...options.init,
                    method,
                    signal:
                        signals.length > 1
                            ? AbortSignal.any(signals)
                            : signals[0],
                    headers: {
                        Accept: 'application/json',
                        ...(body === undefined
                            ? {}
                            : { 'Content-Type': 'application/json' }),
                        ...this.options.headers,
                        ...(options.init?.headers as
                            | Record<string, string>
                            | undefined),
                        Authorization: `Bearer ${this.options.apiKey}`,
                    },
                    body: body === undefined ? undefined : JSON.stringify(body),
                });
            } catch (error) {
                if (options.signal?.aborted || attempt === attempts) {
                    throw new FlexaError(
                        error instanceof Error
                            ? `Flexa request failed: ${error.message}`
                            : 'Flexa request failed.',
                        0,
                    );
                }

                lastError = error;
                await sleep(this.backoff(attempt));
                continue;
            }

            if (response.ok) {
                return (
                    response.status === 204 ? undefined : await response.json()
                ) as T;
            }

            if (attempt < attempts && RETRYABLE_STATUSES.has(response.status)) {
                await sleep(
                    this.backoff(attempt, response.headers.get('Retry-After')),
                );
                continue;
            }

            throw await this.toError(response);
        }

        throw lastError;
    }

    private backoff(attempt: number, retryAfter?: string | null): number {
        const seconds = Number(retryAfter);

        if (retryAfter && Number.isFinite(seconds)) {
            return Math.min(seconds * 1000, 30_000);
        }

        return 250 * 2 ** (attempt - 1);
    }

    private async toError(response: Response): Promise<FlexaError> {
        let body: unknown = null;

        try {
            body = await response.json();
        } catch {
            // Not a JSON body.
        }

        const payload = (body ?? {}) as { message?: unknown; errors?: unknown };
        const message =
            typeof payload.message === 'string' && payload.message !== ''
                ? payload.message
                : `Flexa request failed with status ${response.status}.`;

        return new FlexaError(
            message,
            response.status,
            (payload.errors && typeof payload.errors === 'object'
                ? payload.errors
                : {}) as Record<string, string[]>,
            body,
        );
    }
}

/**
 * Entries of one entity: list, read, create, update, delete and restore.
 */
export class EntityClient<TValues extends object = EntryValues> {
    constructor(
        private readonly client: RequestSender,
        readonly slug: string,
    ) {}

    private get path(): string {
        return `/${encodeURIComponent(this.slug)}`;
    }

    /** Fields, groups, categories and locales of the entity. */
    async schema(options?: RequestOptions): Promise<EntitySchema> {
        const response = await this.client.request<{ data: EntitySchema }>(
            'GET',
            `${this.path}/schema`,
            undefined,
            options,
        );

        return response.data;
    }

    /** One page of entries. */
    list(
        params: ListParams = {},
        options?: RequestOptions,
    ): Promise<Paginated<Entry<TValues>>> {
        const query = new URLSearchParams();

        for (const [key, value] of Object.entries({
            status: params.status,
            search: params.search,
            category: params.category,
            page: params.page,
            per_page: params.perPage,
            trashed: params.trashed,
            include: params.includeRelations ? 'relations' : undefined,
        })) {
            if (value !== undefined && value !== null && value !== '') {
                query.set(key, String(value));
            }
        }

        const search = query.toString();

        return this.client.request(
            'GET',
            `${this.path}${search ? `?${search}` : ''}`,
            undefined,
            options,
        );
    }

    /** Iterate over every matching entry, fetching pages as needed. */
    async *iterate(
        params: Omit<ListParams, 'page'> = {},
        options?: RequestOptions,
    ): AsyncGenerator<Entry<TValues>> {
        let page = 1;
        let lastPage = 1;

        do {
            const result = await this.list(
                { perPage: 100, ...params, page },
                options,
            );

            yield* result.data;
            lastPage = result.meta.last_page;
            page++;
        } while (page <= lastPage);
    }

    /** Every matching entry, across all pages. */
    async all(
        params: Omit<ListParams, 'page'> = {},
        options?: RequestOptions,
    ): Promise<Entry<TValues>[]> {
        const entries: Entry<TValues>[] = [];

        for await (const entry of this.iterate(params, options)) {
            entries.push(entry);
        }

        return entries;
    }

    /** An entry by id or slug. Throws a 404 `FlexaError` when it does not exist. */
    async get(
        idOrSlug: number | string,
        params: GetParams = {},
        options?: RequestOptions,
    ): Promise<Entry<TValues>> {
        const query = params.includeRelations ? '?include=relations' : '';
        const response = await this.client.request<{ data: Entry<TValues> }>(
            'GET',
            `${this.path}/${encodeURIComponent(String(idOrSlug))}${query}`,
            undefined,
            options,
        );

        return response.data;
    }

    /** Like `get()`, but resolves to `null` when the entry does not exist. */
    async find(
        idOrSlug: number | string,
        params: GetParams = {},
        options?: RequestOptions,
    ): Promise<Entry<TValues> | null> {
        try {
            return await this.get(idOrSlug, params, options);
        } catch (error) {
            if (error instanceof FlexaError && error.isNotFound) {
                return null;
            }

            throw error;
        }
    }

    /** Create an entry. Needs the Write ability. */
    async create(
        input: EntryInput<TValues>,
        options?: RequestOptions,
    ): Promise<Entry<TValues>> {
        const response = await this.client.request<{ data: Entry<TValues> }>(
            'POST',
            this.path,
            input,
            options,
        );

        return response.data;
    }

    /** Partially update an entry. Needs the Write ability. */
    async update(
        idOrSlug: number | string,
        input: EntryInput<TValues>,
        options?: RequestOptions,
    ): Promise<Entry<TValues>> {
        const response = await this.client.request<{ data: Entry<TValues> }>(
            'PATCH',
            `${this.path}/${encodeURIComponent(String(idOrSlug))}`,
            input,
            options,
        );

        return response.data;
    }

    /** Move an entry to the trash, or delete it for good with `force`. Needs the Delete ability. */
    async delete(
        idOrSlug: number | string,
        params: { force?: boolean } = {},
        options?: RequestOptions,
    ): Promise<void> {
        await this.client.request(
            'DELETE',
            `${this.path}/${encodeURIComponent(String(idOrSlug))}${params.force ? '?force=1' : ''}`,
            undefined,
            options,
        );
    }

    /** Restore a trashed entry. Needs the Write ability. */
    async restore(
        idOrSlug: number | string,
        options?: RequestOptions,
    ): Promise<Entry<TValues>> {
        const response = await this.client.request<{ data: Entry<TValues> }>(
            'POST',
            `${this.path}/${encodeURIComponent(String(idOrSlug))}/restore`,
            undefined,
            options,
        );

        return response.data;
    }
}

export function createClient<TSchema extends object = ContentSchema>(
    options: FlexaClientOptions,
): FlexaClient<TSchema> {
    return new FlexaClient<TSchema>(options);
}
