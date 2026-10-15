# @flexacms/react

React provider and hooks for the [Flexa](https://github.com/flexacms) headless CMS.

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

`useEntry(entity, idOrSlug)` returns one entry (`null` when it does not exist) and waits while the identifier is `null` or `undefined`. Stale responses are cancelled when the arguments change.

> These hooks run in the browser, so the API key is public. Use a **Read**-only key limited to the entities the site shows, or fetch on the server with [`@flexacms/next`](../next).

## Typed hooks

```ts
import { createFlexaHooks } from '@flexacms/react';
import type { FlexaSchema } from './flexa'; // npx @flexacms/cli generate

export const { useEntries, useEntry } = createFlexaHooks<FlexaSchema>();
```
