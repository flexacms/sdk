# @flexacms/react

Provider and hooks for React apps that run in the browser (for example Vite SPAs). The API key is public in this setup, so use a **Read**-only key limited to the entities the site shows. For SSR frameworks, prefer [next.md](next.md), [nuxt.md](nuxt.md) or [sveltekit.md](sveltekit.md), which keep the key on the server.

```bash
npm install @flexacms/react
```

```tsx
import { createClient, FlexaProvider, useEntries } from '@flexacms/react';

const flexa = createClient({
    apiKey: import.meta.env.VITE_FLEXA_API_KEY,
    // url: import.meta.env.VITE_FLEXA_URL, // optional, defaults to https://flexacms.com
});

export function App() {
    return (
        <FlexaProvider client={flexa}>
            <Posts />
        </FlexaProvider>
    );
}

function Posts() {
    const { data, error, isLoading, refetch } = useEntries('posts', {
        status: 'published',
    });

    if (isLoading) return <p>Loading…</p>;
    if (error) return <p>{error.message}</p>;

    return (
        <ul>
            {data?.data.map((post) => (
                <li key={post.id}>{post.title}</li>
            ))}
        </ul>
    );
}
```

## Hooks

- `useEntries(entity, params)` returns the list response: `data.data` holds the entries.
- `useEntry(entity, idOrSlug)` returns one entry, or `null` when it does not exist. It waits while the identifier is `null` or `undefined`.
- Stale responses are cancelled when the arguments change.

## Typed hooks

```ts
import { createFlexaHooks } from '@flexacms/react';
import type { FlexaSchema } from './flexa'; // npx @flexacms/cli generate

export const { useEntries, useEntry } = createFlexaHooks<FlexaSchema>();
```
