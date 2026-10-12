// Compile-time check (run by `npm run typecheck`): generated types make the client fully typed.
import { createClient } from '@flexacms/client';
import type { FlexaSchema } from './fixtures/flexa';

const client = createClient<FlexaSchema>({
    url: 'https://cms.test',
    apiKey: 'k',
});

export async function check(): Promise<void> {
    const page = await client.entity('pages').get('home');
    const headline: string | null | undefined = page.values.headline.en;
    const layout = page.values.sections?.[0]?.layout;
    const narrowed:
        | 'text'
        | 'image_left'
        | 'image_right'
        | 'grid'
        | null
        | undefined = layout;
    const gallery: string[] | null = (await client.entity('posts').list())
        .data[0]!.values.gallery;
    // @ts-expect-error unknown field on a typed entity
    void page.values.nope;
    const untyped = await client.entity('anything').get(1);
    void untyped.values.whatever;
    void headline;
    void narrowed;
    void gallery;
}
