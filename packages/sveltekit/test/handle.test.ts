import { describe, expect, it, vi } from 'vitest';
import { createFlexaHandle, FlexaClient } from '../src';

describe('createFlexaHandle', () => {
    it('puts a client using the event fetch on locals', async () => {
        const eventFetch = vi.fn(
            async (_input: string | URL | Request, _init?: RequestInit) =>
                new Response(JSON.stringify({ data: [] })),
        );
        const event = {
            locals: {} as { flexa?: FlexaClient },
            fetch: eventFetch as unknown as typeof fetch,
        };
        const resolve = vi.fn(async () => new Response('ok'));

        const response = await createFlexaHandle({
            url: 'https://cms.test',
            apiKey: 'k',
        })({ event, resolve });

        expect(await response.text()).toBe('ok');
        expect(resolve).toHaveBeenCalledWith(event);
        expect(event.locals.flexa).toBeInstanceOf(FlexaClient);

        await event.locals.flexa!.entities();
        expect(eventFetch.mock.calls[0]?.[0] as string).toBe(
            'https://cms.test/api/v1/entities',
        );
    });

    it('requires the url and api key', () => {
        vi.stubEnv('FLEXA_URL', '');
        expect(() => createFlexaHandle()).toThrow('url');
        vi.unstubAllEnvs();
    });
});
