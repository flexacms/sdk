# @flexacms/cli

Generates TypeScript types from the content models of a Flexa workspace.

```bash
FLEXA_API_KEY=flx_... npx @flexacms/cli generate --out src/flexa.d.ts
```

| Option | Default |
| --- | --- |
| `--url` | `$FLEXA_URL`, then `https://flexacms.com` |
| `--key` | `$FLEXA_API_KEY` (needs the Read ability) |
| `--out` | `flexa.d.ts` |

The file contains one `<Entity>Values` interface per content model and a `FlexaSchema` map. Pass `FlexaSchema` to any Flexa SDK that accepts a generic:

```ts
import type { FlexaSchema } from './flexa';

const flexa = createClient<FlexaSchema>({ url, apiKey });
```

Type mapping:
- translatable fields become `Localized<T>`
- selects become unions of their options
- repeaters become arrays of typed items
- relationships become ids
- optional fields are `| null`

Run the command again whenever a content model changes.
