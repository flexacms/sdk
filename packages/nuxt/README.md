# @flexacms/nuxt

[Flexa](https://github.com/flexacms) for Nuxt 3 and 4. Composables are auto-imported and fetch through a **read-only server proxy**, so the API key never reaches the browser.

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
NUXT_FLEXA_URL=https://cms.example.com
NUXT_FLEXA_API_KEY=flx_...
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

Both are `useFetch` calls: they render during SSR, hydrate on the client and refetch when reactive arguments change. In server routes, `useFlexaClient()` gives the full client (writes included):

```ts
// server/api/subscribe.post.ts
export default defineEventHandler(async (event) => {
    const body = await readBody(event);

    return useFlexaClient()
        .entity('subscribers')
        .create({ title: body.email, values: { email: body.email } });
});
```

The proxy (`/api/_flexa/**` by default, `flexa.proxyPath` to change it) only allows GET requests for entities, entries and schemas, and drops parameters such as `trashed`, whatever abilities the key has.

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
