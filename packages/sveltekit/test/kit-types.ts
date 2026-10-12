// Compile-time check (run by `npm run typecheck`): the hook fits SvelteKit's own Handle type.
import type { Handle } from '@sveltejs/kit';
import { createFlexaHandle } from '../src';

export const handle: Handle = createFlexaHandle({
    url: 'https://cms.test',
    apiKey: 'k',
});
