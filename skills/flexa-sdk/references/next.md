# @flexacms/next

Next.js App Router integration. Server-side fetching goes through Next's data cache, tagged per entity and entry for precise revalidation.

```bash
npm install @flexacms/next
```

```env
FLEXA_URL=https://cms.example.com
FLEXA_API_KEY=flx_...
FLEXA_REVALIDATE_SECRET=...     # only if exposing the revalidation route
```

```ts
// lib/flexa.ts
import { createFlexaClient } from '@flexacms/next';
import type { FlexaSchema } from './flexa'; // optional: npx @flexacms/cli generate

export const flexa = createFlexaClient<FlexaSchema>({ revalidate: 300 });
```

```tsx
// app/blog/[slug]/page.tsx (Server Component)
import { notFound } from 'next/navigation';
import { flexa } from '@/lib/flexa';

export default async function Post({
    params,
}: {
    params: Promise<{ slug: string }>;
}) {
    const post = await flexa.entity('posts').find((await params).slug);

    if (!post) notFound();

    return <h1>{post.title}</h1>;
}
```

## Caching

Reads are tagged `flexa`, `flexa:<entity>` and `flexa:<entity>:<id or slug>`. Writes are never cached. `revalidate` sets how long cached responses live: default 60 seconds, `false` keeps them until revalidated.

## Revalidation

```ts
import { revalidateFlexa } from '@flexacms/next';

revalidateFlexa();                   // everything
revalidateFlexa('posts');            // one entity
revalidateFlexa('posts', 'hello');   // one entry
```

Expose a webhook route:

```ts
// app/api/flexa/revalidate/route.ts
import { createRevalidateHandler } from '@flexacms/next';

export const POST = createRevalidateHandler({
    secret: process.env.FLEXA_REVALIDATE_SECRET,
});
```

Called with `Authorization: Bearer <secret>` and a JSON body such as `{"entity":"posts","entry":"hello"}`.

Import `createFlexaClient` only from server code. For client components, use [react.md](react.md) with a Read-only key.
