import type { Entry, GetParams, ListParams, Paginated } from '@flexacms/client';
import { useFetch, useRuntimeConfig } from '#imports';
import { computed, toValue } from 'vue';
import type { MaybeRefOrGetter } from 'vue';
import type { EntityName, ValuesOf } from '../types';

function proxyBase(): string {
    return (useRuntimeConfig().public.flexa as { proxyPath: string }).proxyPath;
}

function listQuery(params: ListParams): Record<string, string | number> {
    const query: Record<string, string | number> = {};

    for (const [key, value] of Object.entries({
        status: params.status,
        search: params.search,
        category: params.category,
        page: params.page,
        per_page: params.perPage,
        include: params.includeRelations ? 'relations' : undefined,
    })) {
        if (value !== undefined && value !== '') {
            query[key] = value;
        }
    }

    return query;
}

/**
 * A page of entries, fetched during SSR and hydrated on the client. Params can
 * be refs or getters: the request runs again when they change.
 */
export function useFlexaEntries<TEntity extends EntityName>(
    entity: MaybeRefOrGetter<TEntity>,
    params: MaybeRefOrGetter<ListParams> = {},
) {
    const base = proxyBase();
    const query = computed(() => listQuery(toValue(params)));

    return useFetch<Paginated<Entry<ValuesOf<TEntity>>>>(
        () => `${base}/${encodeURIComponent(toValue(entity))}`,
        {
            query,
            // Library composables need an explicit key: Nuxt's automatic keys are
            // per call site, which would be shared by every use of this function.
            key: `flexa:${toValue(entity)}:${JSON.stringify(query.value)}`,
            watch: [query],
        },
    );
}

/**
 * One entry by id or slug. When it does not exist, `error` holds the 404.
 */
export function useFlexaEntry<TEntity extends EntityName>(
    entity: MaybeRefOrGetter<TEntity>,
    idOrSlug: MaybeRefOrGetter<number | string>,
    params: GetParams = {},
) {
    const base = proxyBase();

    return useFetch(
        () =>
            `${base}/${encodeURIComponent(toValue(entity))}/${encodeURIComponent(String(toValue(idOrSlug)))}`,
        {
            query: params.includeRelations ? { include: 'relations' } : {},
            key: `flexa:${toValue(entity)}:${String(toValue(idOrSlug))}`,
            transform: (response: { data: Entry<ValuesOf<TEntity>> }) =>
                response.data,
        },
    );
}
