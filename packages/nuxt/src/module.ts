import {
    addImports,
    addServerHandler,
    addServerImports,
    createResolver,
    defineNuxtModule,
} from '@nuxt/kit';
import { defu } from 'defu';
import { DEFAULT_URL } from '@flexacms/client';

export type { FlexaRegister } from './types';
export * from '@flexacms/client';

export interface ModuleOptions {
    /** Flexa URL. Defaults to https://flexacms.com. Prefer the NUXT_FLEXA_URL environment variable. */
    url?: string;
    /** API key, kept server-side. Prefer the NUXT_FLEXA_API_KEY environment variable. */
    apiKey?: string;
    /** Path of the content API. Defaults to `/api/v1`. */
    apiPath?: string;
    /** Route of the read-only proxy the composables call. Defaults to `/api/_flexa`. */
    proxyPath?: string;
}

export default defineNuxtModule<ModuleOptions>({
    meta: {
        name: '@flexacms/nuxt',
        configKey: 'flexa',
        compatibility: { nuxt: '>=3.12.0' },
    },
    defaults: {
        apiPath: '/api/v1',
        proxyPath: '/api/_flexa',
    },
    setup(options, nuxt) {
        const resolver = createResolver(import.meta.url);
        const proxyPath = `/${(options.proxyPath ?? '/api/_flexa').replace(/^\/+|\/+$/g, '')}`;

        // Private runtime config: only available on the server, overridable with
        // NUXT_FLEXA_URL / NUXT_FLEXA_API_KEY at runtime.
        nuxt.options.runtimeConfig.flexa = defu(
            nuxt.options.runtimeConfig.flexa as
                | Record<string, unknown>
                | undefined,
            {
                url: options.url || DEFAULT_URL,
                apiKey: options.apiKey ?? '',
                apiPath: options.apiPath ?? '/api/v1',
            },
        );

        nuxt.options.runtimeConfig.public.flexa = defu(
            nuxt.options.runtimeConfig.public.flexa as
                | Record<string, unknown>
                | undefined,
            {
                proxyPath,
            },
        );

        addServerHandler({
            route: `${proxyPath}/**`,
            method: 'get',
            handler: resolver.resolve('./runtime/server/proxy'),
        });

        addImports([
            {
                name: 'useFlexaEntries',
                from: resolver.resolve('./runtime/composables'),
            },
            {
                name: 'useFlexaEntry',
                from: resolver.resolve('./runtime/composables'),
            },
        ]);

        addServerImports([
            {
                name: 'useFlexaClient',
                from: resolver.resolve('./runtime/server/client'),
            },
        ]);
    },
});
