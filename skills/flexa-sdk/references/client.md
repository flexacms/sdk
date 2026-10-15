# @flexacms/client

Typed, dependency-free client. Works in Node 20+, Bun, Deno, edge runtimes and browsers.

```ts
import { createClient, localize } from '@flexacms/client';

const flexa = createClient({
    apiKey: process.env.FLEXA_API_KEY!,
    // url: 'https://staging.example.com', // optional, defaults to https://flexacms.com
});

const { data: posts, meta } = await flexa
    .entity('posts')
    .list({ status: 'published', perPage: 12 });

const post = await flexa.entity('posts').find('hello-world'); // null when missing
const headline = localize(post?.values.headline, 'es', ['en']);
```

## Methods

| Method | Description |
| --- | --- |
| `entities()` | Content models the key can read |
| `entity(slug).schema()` | Fields, groups, categories and locales |
| `entity(slug).list(params)` | One page. Params: `status`, `search`, `category`, `page`, `perPage`, `trashed`, `includeRelations` |
| `entity(slug).all(params)` / `iterate(params)` | Every matching entry, page by page |
| `entity(slug).get(idOrSlug)` | One entry; throws on 404 |
| `entity(slug).find(idOrSlug)` | One entry; resolves to `null` on 404 |
| `entity(slug).create(input)` / `update(id, input)` | Write entries (Write ability) |
| `entity(slug).delete(id, { force })` / `restore(id)` | Trash, delete for good, or restore |

Every method accepts a last `{ signal, init }` argument. `init` is merged into the `fetch` call, which is how to pass an `AbortSignal` or extra options.

## Errors

Failed requests throw `FlexaError` with `status`, `message`, `errors` (keyed by field) and helpers `isNotFound` and `isValidationError`.

Reads retry after `429`, `502`, `503`, `504` and network failures, honouring `Retry-After`. Writes are never retried.

## Options

| Option | Default | Notes |
| --- | --- | --- |
| `apiPath` | `/api/v1` | Path of the content API |
| `timeout` | `30000` | Milliseconds before a request is aborted |
| `retries` | `2` | Retries for reads |
| `headers` | none | Extra headers on every request |
| `fetch` | global `fetch` | Custom implementation |

## Typed content

```ts
import type { FlexaSchema } from './flexa'; // npx @flexacms/cli generate

const flexa = createClient<FlexaSchema>({ url, apiKey });
const page = await flexa.entity('pages').get('home');
```

Keep the API key on the server. Anyone with it can do what its abilities allow.
