# @flexacms/sveltekit

Server hook that puts a client on `event.locals.flexa`, available in every server `load` function and form action.

```bash
npm install @flexacms/sveltekit
```

```ts
// src/hooks.server.ts
import { env } from '$env/dynamic/private';
import { createFlexaHandle } from '@flexacms/sveltekit';

export const handle = createFlexaHandle({
    // url defaults to FLEXA_URL, then https://flexacms.com
    url: env.FLEXA_URL,
    apiKey: env.FLEXA_API_KEY,
});
```

```ts
// src/app.d.ts
import type { FlexaClient } from '@flexacms/sveltekit';
import type { FlexaSchema } from './flexa'; // optional: npx @flexacms/cli generate

declare global {
    namespace App {
        interface Locals {
            flexa: FlexaClient<FlexaSchema>;
        }
    }
}

export {};
```

```ts
// src/routes/blog/[slug]/+page.server.ts
import { error } from '@sveltejs/kit';

export async function load({ locals, params }) {
    const post = await locals.flexa.entity('posts').find(params.slug);

    if (!post) error(404);

    return { post };
}
```

The client uses SvelteKit's `event.fetch`. To combine this hook with others, use `sequence()` from `@sveltejs/kit/hooks`.
