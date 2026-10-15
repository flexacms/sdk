# @flexacms/client

Typed, dependency-free client for the [Flexa](https://github.com/flexacms) headless CMS content API. Works in Node 20+, Bun, Deno, edge runtimes and browsers.

```bash
npm install @flexacms/client
```

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

> Keep the API key on the server. Anyone with it can do what its abilities allow; use a **Read** key limited to the entities a public site needs.

## Typed content

Generate types for your workspace with [`@flexacms/cli`](../cli) and pass them to the client:

```ts
import type { FlexaSchema } from './flexa';

const flexa = createClient<FlexaSchema>({ url, apiKey });
const page = await flexa.entity('pages').get('home');
page.values.headline.en; // string | null | undefined, typed from your content model
```

## API

| Method                                               | Description                                                                                |
| ---------------------------------------------------- | ------------------------------------------------------------------------------------------ |
| `entities()`                                         | Content models the key can read                                                            |
| `entity(slug).schema()`                              | Fields, groups, categories and locales                                                     |
| `entity(slug).list(params)`                          | One page: `status`, `search`, `category`, `page`, `perPage`, `trashed`, `includeRelations` |
| `entity(slug).all(params)` / `iterate(params)`       | Every matching entry, page by page                                                         |
| `entity(slug).get(idOrSlug)` / `find(idOrSlug)`      | One entry (`find` resolves to `null` on 404)                                               |
| `entity(slug).create(input)` / `update(id, input)`   | Write entries (Write ability)                                                              |
| `entity(slug).delete(id, { force })` / `restore(id)` | Trash, delete for good or restore                                                          |

Every method accepts a last `{ signal, init }` argument; `init` is merged into the `fetch` call.

## Errors and retries

Failed requests throw a `FlexaError` with `status`, `message`, `errors` (validation errors keyed by field) and helpers such as `isNotFound` or `isValidationError`. Reads are retried after `429`, `502`, `503`, `504` and network failures (honouring `Retry-After`); writes are never retried.

| Option    | Default        |                                          |
| --------- | -------------- | ---------------------------------------- |
| `apiPath` | `/api/v1`      | Path of the content API                  |
| `timeout` | `30000`        | Milliseconds before a request is aborted |
| `retries` | `2`            | Retries for reads                        |
| `headers` | —              | Extra headers on every request           |
| `fetch`   | global `fetch` | Custom fetch implementation              |
