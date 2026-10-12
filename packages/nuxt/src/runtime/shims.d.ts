/**
 * Type stand-ins for Nuxt's virtual `#imports` module, which only exists inside
 * a Nuxt app. They cover the members the runtime files use.
 */
declare module '#imports' {
    import type { Ref } from 'vue';

    export function useRuntimeConfig(): Record<string, unknown> & {
        public: Record<string, unknown>;
    };

    export function useFetch<TResponse, TData = TResponse>(
        url: string | (() => string),
        options?: {
            query?: unknown;
            key?: string;
            watch?: unknown[];
            transform?: (response: TResponse) => TData;
        },
    ): Promise<{
        data: Ref<TData | null>;
        error: Ref<Error | null>;
        status: Ref<'idle' | 'pending' | 'success' | 'error'>;
        refresh: () => Promise<void>;
    }>;
}
