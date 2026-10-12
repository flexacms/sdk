import { createClient } from '@flexacms/client';
import type { FlexaClient } from '@flexacms/client';
import { useRuntimeConfig } from '#imports';
import type { RegisteredSchema } from '../../types';

let client: FlexaClient<RegisteredSchema> | undefined;

/**
 * The server-side Flexa client (auto-imported in server routes). It has the
 * full abilities of the API key, so never return it to the browser.
 */
export function useFlexaClient(): FlexaClient<RegisteredSchema> {
    if (client) {
        return client;
    }

    const config = useRuntimeConfig().flexa as {
        url: string;
        apiKey: string;
        apiPath: string;
    };

    if (!config.url || !config.apiKey) {
        throw new Error(
            'Flexa: set NUXT_FLEXA_URL and NUXT_FLEXA_API_KEY (or flexa.url / flexa.apiKey in nuxt.config).',
        );
    }

    client = createClient<RegisteredSchema>({
        url: config.url,
        apiKey: config.apiKey,
        apiPath: config.apiPath,
    });

    return client;
}
