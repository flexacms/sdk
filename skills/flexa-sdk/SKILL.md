---
name: flexa-sdk
description: Use for any work with the Flexa headless CMS — designing or changing content models (entities, fields, field groups, categories), proposing a data structure for a website or app, managing content through the Flexa MCP server or the REST API at /api/v1, and reading or writing content with an official SDK (@flexacms/client, @flexacms/react, @flexacms/next, @flexacms/nuxt, @flexacms/astro, @flexacms/sveltekit, @flexacms/cli, flexacms/php, flexacms/laravel). Triggers on Flexa, flexacms, FLEXA_API_KEY, FLEXA_URL, flx_ keys, Flexa MCP tools (list_entities, get_entity_schema, create_field...), "content model", "headless CMS" content fetching, or code importing from any @flexacms/* package or the FlexaCms\ namespaces.
---

# Flexa

Flexa is a multi-tenant headless CMS. Everything belongs to one **workspace**, and an API key (`flx_...`) only ever sees its own workspace.

| Concept         | What it is                                                                                                                                                           |
| --------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **Entity**      | A content model (`pages`, `posts`, `products`). Has a unique slug, typed fields, groups, categories and entries.                                                     |
| **Field**       | A typed value of an entity (`text`, `image`, `repeater`, `relationship`...). Identified by a snake_case `key`.                                                       |
| **Field group** | A labeled, collapsible section of the entry form (`SEO`, `Hero`). Layout only: it does not change the API data.                                                      |
| **Category**    | A per-entity, hierarchical taxonomy. Entries are assigned to categories, and **fields can be scoped to categories** so each kind of entry shows only its own fields. |
| **Entry**       | A record of an entity: `title`, `slug` (unique per entity), `status`, `categories`, `values`, `media`.                                                               |

Statuses: `draft`, `published`, `scheduled` (needs `publish_at`), `archived`. Every change is stored as a revision and attributed to the user or the API key.

## Pick the task

| You need to...                                              | Read                                                             |
| ----------------------------------------------------------- | ---------------------------------------------------------------- |
| Propose or design a content structure, add or change models | [references/content-modeling.md](references/content-modeling.md) |
| Know a field type, its settings or the value it expects     | [references/field-types.md](references/field-types.md)           |
| Work through the Flexa MCP server (tools, workflows)        | [references/mcp.md](references/mcp.md)                           |
| Call the REST API directly, or handle webhooks              | [references/rest-api.md](references/rest-api.md)                 |
| Fetch content from an app with an SDK                       | The stack's reference in the table below                         |

Read only the references the task needs. For any modeling work, read `content-modeling.md` **before** creating anything.

## Pick the SDK

| Stack              | Install                             | Reference                                          |
| ------------------ | ----------------------------------- | -------------------------------------------------- |
| Laravel 12–13      | `composer require flexacms/laravel` | [references/laravel.md](references/laravel.md)     |
| Other PHP 8.1+     | `composer require flexacms/php`     | [references/php.md](references/php.md)             |
| Next.js App Router | `npm install @flexacms/next`        | [references/next.md](references/next.md)           |
| React (SPA, Vite)  | `npm install @flexacms/react`       | [references/react.md](references/react.md)         |
| Nuxt 3/4           | `npm install @flexacms/nuxt`        | [references/nuxt.md](references/nuxt.md)           |
| Astro 5+           | `npm install @flexacms/astro`       | [references/astro.md](references/astro.md)         |
| SvelteKit          | `npm install @flexacms/sveltekit`   | [references/sveltekit.md](references/sveltekit.md) |
| Any JS runtime     | `npm install @flexacms/client`      | [references/client.md](references/client.md)       |
| Typed content (TS) | `npx @flexacms/cli generate`        | [references/cli.md](references/cli.md)             |

Framework packages re-export `@flexacms/client`, so installing one framework package is enough.

| Variable        | Purpose             | Default                    |
| --------------- | ------------------- | -------------------------- |
| `FLEXA_URL`     | Base URL of the CMS | `https://app.flexacms.com` |
| `FLEXA_API_KEY` | API key (`flx_...`) | none, required             |

Framework prefixes apply: Nuxt uses `NUXT_FLEXA_*`, Vite-based apps expose variables through `VITE_*`, and Astro/SvelteKit read them through `import.meta.env` or `$env/dynamic/private`. Check each reference for the exact name.

## API key abilities

| Ability    | Allows                                                                              |
| ---------- | ----------------------------------------------------------------------------------- |
| **Read**   | Read models, schemas and entries                                                    |
| **Write**  | Create, update, publish, unpublish and restore entries                              |
| **Delete** | Delete entries; with **Schema**, over MCP also fields, groups, categories, entities |
| **Schema** | Create and change content models (**MCP only**, not the REST API)                   |
| **MCP**    | Connect to the MCP server at `/mcp`                                                 |

A key can also be restricted to some entities and can expire. If a tool or endpoint you expect is missing, the key lacks the ability: tell the user which one to add instead of working around it.

## Rules that prevent bugs

- **Inspect before writing.** Read the entity schema (`get_entity_schema` or `GET /api/v1/{entity}/schema`) before creating entries or changing a model. Use the exact field keys and follow each field's `value_format`.
- **Unknown field keys are silently dropped.** A typo does not fail; it just loses the value. After every write, read the entry back and check the values you sent are there.
- **Keep the API key on the server** whenever the stack has a server. Only browser-side code exposes the key; then use a **Read** key restricted to the entities the site shows.
- **Writes are never retried by the SDKs; reads are.** Do not blindly repeat a failed create: check whether it was created first.
- **`find()` returns `null` on 404; `get()` throws.**
- **Translatable values are objects keyed by locale** (`{"en": "...", "es": "..."}`). Read them with `localize()` (JS) or `value('field', 'es')` (PHP). The workspace's locales are in the schema's `locales`; the first one is the fallback.
- **Filter by status explicitly.** Astro loads only published entries by default; the other SDKs return what the query asks for. Pass `status: 'published'` (or `published()`) on public pages.
- **Errors are `FlexaError` (JS) or `FlexaCms\...\FlexaException` (PHP)** with `status` and `errors` keyed by field. Handle 404 and 422 separately.
- **Caching is on for reads.** Next.js revalidates with tags; Laravel and PHP cache 60 seconds by default. Use webhooks or a short TTL for content that must update at once.
- **Ask before destructive or structural changes in an existing workspace.** Deleting a field, entity or category deletes its data; renaming a key or slug breaks every frontend that reads it.

## Do not invent

Flexa answers precisely, so guessing is never needed:

- Field types, tool names, endpoints, query parameters and SDK methods are the ones listed in these references. If something is not listed, it does not exist: say so and propose a supported alternative.
- The live schema is the source of truth for a workspace. When it disagrees with these references (a newer version), follow the schema and the tool descriptions.
- Things Flexa does **not** have: singleton entities (use one entry with a fixed slug), file uploads through the API or MCP (files are uploaded in the panel), per-field permissions, computed fields, field inheritance between parent and child categories, nested entities. Do not promise them.
- When a reference does not answer an SDK question, read the package README (`https://github.com/flexacms/sdk/tree/main/packages/<name>`) before guessing a method name.
