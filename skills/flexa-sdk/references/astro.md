# @flexacms/astro

Content layer loader for Astro 5+ content collections.

```bash
npm install @flexacms/astro
```

```ts
// src/content.config.ts
import { defineCollection } from 'astro:content';
import { flexaLoader } from '@flexacms/astro';

const posts = defineCollection({
    loader: flexaLoader({
        entity: 'posts',
        apiKey: import.meta.env.FLEXA_API_KEY,
        // url: import.meta.env.FLEXA_URL, // optional, defaults to https://flexacms.com
    }),
});

export const collections = { posts };
```

```astro
---
import { getCollection } from 'astro:content';

const posts = await getCollection('posts');
---
{posts.map((post) => <a href={`/blog/${post.id}`}>{post.data.title}</a>)}
```

Each entry is stored with its slug as `id` (or its numeric id when it has no slug). The full API entry is stored as `data`. Only published entries load by default.

## Options

| Option | Default | Notes |
| --- | --- | --- |
| `entity` | none | Entity slug, required |
| `query` | `{ status: 'published' }` | `status`, `search`, `category`, `perPage`, `includeRelations` |
| `id` | slug, then id | `(entry) => string` |
| `url` | `FLEXA_URL`, then `https://flexacms.com` | |
| `apiKey` | `FLEXA_API_KEY` | |

Add a `schema` to `defineCollection` (for example with Zod) to validate or reshape `data`.
