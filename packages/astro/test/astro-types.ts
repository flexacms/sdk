// Compile-time check (run by `npm run typecheck`): the loader fits Astro's own Loader type.
import type { Loader } from 'astro/loaders';
import { flexaLoader } from '../src';

export const loader: Loader = flexaLoader({
    entity: 'posts',
    url: 'https://cms.test',
    apiKey: 'k',
});
