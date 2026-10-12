# @flexacms/cli

Generate TypeScript types from the content models of your [Flexa](https://github.com/flexacms) workspace.

```bash
FLEXA_URL=https://cms.example.com FLEXA_API_KEY=flx_... npx @flexacms/cli generate --out src/flexa.d.ts
```

The file contains one `<Entity>Values` interface per content model and a `FlexaSchema` map. Pass it to any Flexa SDK:

```ts
import type { FlexaSchema } from './flexa';

const flexa = createClient<FlexaSchema>({ url, apiKey });
```

Field types map to their API values: translatable fields become `Localized<T>`, selects become unions of their options, repeaters become arrays of typed items, relationships become ids, and optional fields are `| null`. Run the command again whenever a content model changes.

| Option  | Default                         |
| ------- | ------------------------------- |
| `--url` | `$FLEXA_URL`                    |
| `--key` | `$FLEXA_API_KEY` (Read ability) |
| `--out` | `flexa.d.ts`                    |
