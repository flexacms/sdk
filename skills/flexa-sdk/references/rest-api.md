# REST API and webhooks

Prefer an SDK when one fits the stack. Use the REST API directly for stacks without an SDK, or for parameters the SDKs do not expose (`filter`, `sort`, `locale`, `fields`).

Base: `{FLEXA_URL}/api/v1`. Auth: `Authorization: Bearer flx_...` (or `X-Api-Key: flx_...`), plus `Accept: application/json`. Rate limit: per API key, shared with MCP (600 requests per minute by default), answered with `429` and `Retry-After`.

## Endpoints

| Method    | Path                             | Ability | Description                                               |
| --------- | -------------------------------- | ------- | --------------------------------------------------------- |
| GET       | `/entities`                      | Read    | Content models the key can read                           |
| GET       | `/{entity}/schema`               | Read    | Fields (with `value_format`), groups, categories, locales |
| GET       | `/{entity}`                      | Read    | Paginated entries                                         |
| POST      | `/{entity}`                      | Write   | Create an entry                                           |
| GET       | `/{entity}/{id-or-slug}`         | Read    | One entry                                                 |
| PUT/PATCH | `/{entity}/{id-or-slug}`         | Write   | Partial update                                            |
| DELETE    | `/{entity}/{id-or-slug}`         | Delete  | Soft delete; `?force=1` deletes for good                  |
| POST      | `/{entity}/{id-or-slug}/restore` | Write   | Restore a soft-deleted entry                              |
| GET       | `/_preview/{token}`              | Read    | Entry of a signed preview token, whatever its status      |

The REST API cannot change content models (no Schema endpoints) and cannot upload files.

## Query parameters

| Parameter                                     | Applies to | Effect                                                                                                                                                                                             |
| --------------------------------------------- | ---------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `status`                                      | lists      | `draft`, `published`, `scheduled`, `archived`. Always pass `published` on public pages.                                                                                                            |
| `search`                                      | lists      | Matches title or slug.                                                                                                                                                                             |
| `category`                                    | lists      | Category **ID** (integer). Exact match, children are not included.                                                                                                                                 |
| `filter[key]=value` / `filter[key][op]=value` | lists      | Ops: `eq`, `ne`, `gt`, `gte`, `lt`, `lte`, `in` (comma separated), `contains`, `null` (`true`/`false`). See field-types.md for what each type supports. Unknown fields or unsupported ops → `422`. |
| `sort`                                        | lists      | Field keys or `id`, `title`, `slug`, `order`, `status`, `publish_at`, `created_at`, `updated_at`; `-` prefix for descending: `sort=-price,title`.                                                  |
| `per_page`, `page`                            | lists      | Pagination (max 100 per page). Lists include `links` and `meta`.                                                                                                                                   |
| `trashed`                                     | lists      | `with` or `only` for soft-deleted entries. Needs the **Write** ability (restore permission); a Read key gets `403`.                                                                                |
| `locale`                                      | lists, one | Resolve top-level translatable fields to one locale, with fallback.                                                                                                                                |
| `fields`                                      | lists, one | Only these keys in `values` and `media`: `fields=hero_title,hero_image`.                                                                                                                           |
| `include`                                     | lists, one | `relations` adds a top-level `relations` object (see Entry response); `relations.values` also includes the related entries' `values` and `media`.                                                  |

```bash
curl "$FLEXA_URL/api/v1/products?status=published&filter[price][gte]=10&sort=-price&include=relations" \
  -H "Authorization: Bearer $FLEXA_API_KEY" -H "Accept: application/json"
```

## Entry payload (create / update)

```json
{
    "title": "Pricing",
    "slug": "pricing",
    "status": "draft",
    "publish_at": null,
    "order": 3,
    "categories": ["landing"],
    "values": {
        "hero_title": { "en": "Simple pricing", "es": "Precios simples" },
        "featured_products": [12, 15],
        "hero_cta": {
            "url": "/contact",
            "title": "Talk to us",
            "target": "_self"
        }
    }
}
```

- `categories` accepts IDs, slugs or names of the entity's categories and replaces the entry's categories when sent.
- `values` is partial on update; `data` is accepted as an alias.
- Without `status`, a new entry is `published` if the key may publish, otherwise `draft`. Send `draft` explicitly unless publishing is intended.
- `scheduled` needs `publish_at`; a past date publishes at once.
- Validation errors: `422` with `errors` keyed by field path (`values.price`).

## Entry response

```json
{
    "data": {
        "id": 7,
        "entity_slug": "pages",
        "title": "Pricing",
        "slug": "pricing",
        "status": "published",
        "publish_at": null,
        "order": 3,
        "categories": [{ "id": 4, "name": "Landing", "slug": "landing" }],
        "values": {
            "hero_image": "flex-fields/acme/pages/hero_image/pricing.jpg",
            "featured_products": [12, 15]
        },
        "media": {
            "hero_image": "https://cdn.example.com/flex-fields/acme/pages/hero_image/pricing.jpg"
        },
        "relations": {
            "featured_products": [
                {
                    "id": 12,
                    "title": "Starter",
                    "slug": "starter",
                    "status": "published"
                },
                {
                    "id": 15,
                    "title": "Pro",
                    "slug": "pro",
                    "status": "published"
                }
            ]
        },
        "created_at": "...",
        "updated_at": "...",
        "deleted_at": null
    }
}
```

Render files from `media` (absolute URLs). Send paths from `values` when writing.

`relations` is only present with `include=relations` (or `relations.values`). It is keyed by relationship field and is **always an array**, even for a single relationship. `values` keeps the IDs either way. A relationship to an entity the key cannot read is left out of `relations`. Check each related entry's `status` before rendering it on a public page.

## Preview of unpublished entries

The panel's **Preview** button opens the website with a signed token in the URL. The website's preview URL is set in _Workspace settings → Entry preview_ as a pattern with `{token}` (required), `{entity}`, `{slug}` and `{id}`, for example `https://site.com/preview/{entity}/{slug}?token={token}`. The website exchanges the token at `GET /api/v1/_preview/{token}`, which returns the entry whatever its status, including trashed entries. Tokens expire (60 minutes by default); an expired or invalid token gives `404`. Do not build previews on a plain `?preview=1&id=5` parameter. If the Preview button does not appear, no preview URL is configured for the workspace. The SDKs have no preview helper; call the endpoint with the client's `fetch` or the REST API.

## Webhooks

Configured in the panel (_Developers → Webhooks_), each with its events and optionally limited to some entities. Events: `entry.created`, `entry.updated`, `entry.published`, `entry.unpublished`, `entry.deleted`, `entry.restored`, `schema.updated`, `ping`.

Each delivery is a `POST` with JSON:

```json
{
    "id": "0193...",
    "type": "entry.published",
    "created_at": "2026-11-12T10:00:00+00:00",
    "workspace": "acme",
    "entity": "pages",
    "entry": "pricing",
    "data": {
        "id": 7,
        "slug": "pricing",
        "title": "Pricing",
        "status": "published",
        "entity": "pages"
    }
}
```

`entry` is the slug (or the ID when there is none). `schema.updated` sends `data: { entity, deleted }` and is debounced per entity. `entry.deleted` adds `data.permanently`.

Headers:

| Header            | Value                                                                                            |
| ----------------- | ------------------------------------------------------------------------------------------------ |
| `Flexa-Event`     | Event type                                                                                       |
| `Flexa-Delivery`  | Delivery UUID (use it to ignore duplicates)                                                      |
| `Flexa-Signature` | `t={unix timestamp},v1={hex}`: HMAC-SHA256 of `"{timestamp}.{raw body}"` with the webhook secret |
| `Authorization`   | `Bearer {webhook secret}` (what the SDK revalidation handlers check)                             |

Verify the signature against the **raw** body with a constant-time comparison and reject old timestamps. Answer `2xx` quickly; failed deliveries are retried and visible in the panel.
