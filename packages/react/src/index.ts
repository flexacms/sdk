import type {
    ContentSchema,
    Entry,
    EntryValues,
    FlexaClient,
    GetParams,
    ListParams,
    Paginated,
} from '@flexacms/client';
import {
    createContext,
    createElement,
    useCallback,
    useContext,
    useEffect,
    useRef,
    useState,
} from 'react';
import type { ReactNode } from 'react';

export * from '@flexacms/client';

// eslint-disable-next-line @typescript-eslint/no-explicit-any
const FlexaContext = createContext<FlexaClient<any> | null>(null);

/**
 * Makes a Flexa client available to the hooks below.
 *
 * The client runs in the browser, so its API key is public: use a key with only
 * the Read ability, limited to the entities the site shows. Prefer fetching on
 * the server (e.g. `@flexacms/next`) when you can.
 */
export function FlexaProvider<TSchema extends object = ContentSchema>({
    client,
    children,
}: {
    client: FlexaClient<TSchema>;
    children?: ReactNode;
}) {
    return createElement(FlexaContext.Provider, { value: client }, children);
}

/** The client given to the nearest `FlexaProvider`. */
export function useFlexa<
    TSchema extends object = ContentSchema,
>(): FlexaClient<TSchema> {
    const client = useContext(FlexaContext);

    if (!client) {
        throw new Error('useFlexa() must be used inside a <FlexaProvider>.');
    }

    return client as FlexaClient<TSchema>;
}

export interface QueryState<T> {
    data: T | undefined;
    error: Error | undefined;
    isLoading: boolean;
    /** Fetch again, keeping the current data until the new response arrives. */
    refetch: () => void;
}

/**
 * Run an async request on mount and whenever `key` changes, cancelling stale
 * requests so a slow response never overwrites a newer one.
 */
function useQuery<T>(
    key: string,
    enabled: boolean,
    run: (signal: AbortSignal) => Promise<T>,
): QueryState<T> {
    const [state, setState] = useState<Omit<QueryState<T>, 'refetch'>>({
        data: undefined,
        error: undefined,
        isLoading: enabled,
    });
    const [version, setVersion] = useState(0);
    const runRef = useRef(run);

    runRef.current = run;

    useEffect(() => {
        if (!enabled) {
            setState((current) => ({ ...current, isLoading: false }));

            return;
        }

        const controller = new AbortController();

        setState((current) => ({
            ...current,
            error: undefined,
            isLoading: true,
        }));

        runRef.current(controller.signal).then(
            (data) => {
                if (!controller.signal.aborted) {
                    setState({ data, error: undefined, isLoading: false });
                }
            },
            (error: unknown) => {
                if (!controller.signal.aborted) {
                    setState((current) => ({
                        ...current,
                        error:
                            error instanceof Error
                                ? error
                                : new Error(String(error)),
                        isLoading: false,
                    }));
                }
            },
        );

        return () => controller.abort();
    }, [key, enabled, version]);

    const refetch = useCallback(() => setVersion((current) => current + 1), []);

    return { ...state, refetch };
}

type ValuesFor<
    TSchema extends object,
    TEntity extends string,
> = TEntity extends keyof TSchema
    ? TSchema[TEntity] extends object
        ? TSchema[TEntity]
        : EntryValues
    : EntryValues;

export interface FlexaHooks<TSchema extends object> {
    useFlexa: () => FlexaClient<TSchema>;
    /** A page of entries of an entity. */
    useEntries: <
        TEntity extends Extract<keyof TSchema, string> | (string & {}),
    >(
        entity: TEntity,
        params?: ListParams,
    ) => QueryState<Paginated<Entry<ValuesFor<TSchema, TEntity>>>>;
    /**
     * One entry by id or slug. `data` is `null` when it does not exist; pass
     * `null`/`undefined` as the identifier to wait (e.g. until a route param is known).
     */
    useEntry: <TEntity extends Extract<keyof TSchema, string> | (string & {})>(
        entity: TEntity,
        idOrSlug: number | string | null | undefined,
        params?: GetParams,
    ) => QueryState<Entry<ValuesFor<TSchema, TEntity>> | null>;
}

/**
 * Hooks typed with your generated schema:
 *
 *     export const { useEntries, useEntry } = createFlexaHooks<FlexaSchema>();
 */
export function createFlexaHooks<
    TSchema extends object = ContentSchema,
>(): FlexaHooks<TSchema> {
    return {
        useFlexa: () => useFlexa<TSchema>(),
        useEntries: (entity, params = {}) => {
            const client = useFlexa<TSchema>();

            return useQuery(
                `${entity}:${JSON.stringify(params)}`,
                true,
                (signal) => client.entity(entity).list(params, { signal }),
            ) as never;
        },
        useEntry: (entity, idOrSlug, params = {}) => {
            const client = useFlexa<TSchema>();
            const enabled =
                idOrSlug !== null && idOrSlug !== undefined && idOrSlug !== '';

            return useQuery(
                `${entity}:${String(idOrSlug)}:${JSON.stringify(params)}`,
                enabled,
                (signal) =>
                    client
                        .entity(entity)
                        .find(idOrSlug as number | string, params, { signal }),
            ) as never;
        },
    };
}

const untypedHooks = createFlexaHooks();

/** A page of entries of an entity. Use `createFlexaHooks<FlexaSchema>()` for typed values. */
export const useEntries = untypedHooks.useEntries;

/** One entry by id or slug (`null` when missing). Use `createFlexaHooks<FlexaSchema>()` for typed values. */
export const useEntry = untypedHooks.useEntry;
