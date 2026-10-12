import { isFlexaError } from '@flexacms/client';
import { createError, defineEventHandler, getQuery } from 'h3';
import { proxyRequestPath } from '../proxy-path';
import { useFlexaClient } from './client';

/**
 * GET-only proxy used by the composables, so the browser never sees the key.
 */
export default defineEventHandler(async (event) => {
    const path = proxyRequestPath(
        String(event.context.params?._ ?? ''),
        getQuery(event),
    );

    if (path === null) {
        throw createError({ statusCode: 404, statusMessage: 'Not Found' });
    }

    try {
        return await useFlexaClient().request('GET', path);
    } catch (error) {
        if (isFlexaError(error)) {
            throw createError({
                statusCode: error.status || 502,
                statusMessage: error.message,
            });
        }

        throw error;
    }
});
