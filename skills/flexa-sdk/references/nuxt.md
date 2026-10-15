# @flexacms/nuxt

Nuxt 3 and 4 module. Composables are auto-imported and fetch through a **read-only server proxy**, so the API key never reaches the browser.

```bash
npm install @flexacms/nuxt
```

```ts
// nuxt.config.ts
export default defineNuxtConfig({
    modules: ['@flexacms/nuxt'],
});
```

```env
NUXT_FLEXA_API_KEY=flx_...
# NUXT_FLEXA_URL=https://staging.example.com   # optional, defaults to https://flexacms.com
```

```vue
<script setup lang="ts">
const route = useRoute();
const { data: posts } = await useFlexaEntries('posts', { status: 'published' });
const { data: post, error } = await useFlexaEntry(
    'posts',
    () => route.params.slug as string,
);
</script>
```

Both composables are `useFetch` calls. They render during SSR, hydrate on the client and refetch when reactive arguments change.

## Server routes

`useFlexaClient()` gives the full client, writes included. Use it only in server code:

```ts
// server/api/subscribe.post.ts
export default defineEventHandler(async (event) => {
    const body = await readBody(event);

    return useFlexaClient()
        .entity('subscribers')
        .create({ title: body.email, values: { email: body.email } });
});
```

## Proxy

The proxy lives at `/api/_flexa/**` by default; change it with `flexa.proxyPath`. It allows only GET requests for entities, entries and schemas, and drops parameters such as `trashed`, whatever abilities the key has.

## Typed composables

```ts
// types/flexa-register.d.ts
import type { FlexaSchema } from './flexa'; // npx @flexacms/cli generate

declare module '@flexacms/nuxt' {
    interface FlexaRegister {
        schema: FlexaSchema;
    }
}
```
