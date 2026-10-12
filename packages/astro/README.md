# @flexacms/astro

Load [Flexa](https://github.com/flexacms) content into Astro content collections (Astro 5+ content layer).

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
        url: import.meta.env.FLEXA_URL,
        apiKey: import.meta.env.FLEXA_API_KEY,
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

Each entry is stored with its slug as id (or its numeric id when it has no slug) and the full API entry as `data`. By default only published entries are loaded.

| Option           | Default                       |                                                               |
| ---------------- | ----------------------------- | ------------------------------------------------------------- |
| `entity`         | —                             | Entity slug                                                   |
| `query`          | `{ status: 'published' }`     | `status`, `search`, `category`, `perPage`, `includeRelations` |
| `id`             | slug, then id                 | `(entry) => string`                                           |
| `url` / `apiKey` | `FLEXA_URL` / `FLEXA_API_KEY` |                                                               |

Add a `schema` to `defineCollection` to validate or reshape `data` with Zod.
