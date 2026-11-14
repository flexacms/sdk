# Flexa MCP server

The CMS exposes an MCP server at `{FLEXA_URL}/mcp` (streamable HTTP). It authenticates with an API key that has the **MCP** ability, sent as `Authorization: Bearer flx_...` (or `X-Api-Key`). Everything is scoped to the key's workspace and, when set, to the key's allowed entities.

Connect Claude Code (other clients take the same URL and header):

```bash
claude mcp add --transport http flexa https://app.flexacms.com/mcp \
  --header "Authorization: Bearer flx_..."
```

Never commit the key: keep it in the user's local MCP config or an environment variable.

## Tools by key ability

The server lists only the tools the key allows. A missing tool means a missing ability, not a broken server.

| Ability         | Tools                                                                                                                                                                         |
| --------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Read            | `list_entities`, `get_entity_schema`, `list_fields`, `get_field`, `list_categories`, `list_records`, `get_record`, `list_record_revisions`                                    |
| Write           | `create_record`, `update_record`, `set_record_values`, `restore_record_revision`, `publish_record`, `unpublish_record`                                                        |
| Schema          | `create_entity`, `update_entity`, `create_field`, `update_field`, `assign_field_categories`, `create_category`, `update_category`, `create_field_group`, `update_field_group` |
| Delete          | `delete_record`                                                                                                                                                               |
| Delete + Schema | `delete_field`, `delete_field_group`, `delete_category`, `delete_entity`                                                                                                      |

`entity` arguments accept the slug or numeric ID; `record` accepts the ID or slug; `field` accepts the key or ID.

## Behaviors to know

- **`create_record` defaults to `status: "published"`.** Pass `status: "draft"` unless the user asked to publish. Use `scheduled` + `publish_at` (ISO 8601) for scheduled publishing.
- **The MCP tools cannot set an entry's `order`.** Manual ordering is done by dragging in the panel or with the REST API (`order` in the payload). When the frontend needs an order editors control over MCP, add a `number` field (`position`) and sort by it.
- **`update_record` with `category_ids` replaces the entry's categories** (it does not add). Send the full list. Omit it to keep them.
- **Field values are partial:** `update_record` / `set_record_values` change only the keys sent. A `repeater` or `multiple` value is replaced as a whole: send every row.
- **Unknown keys are dropped without error.** Verify with `get_record` after writing.
- **Required fields are enforced on create** even if hidden by categories or `show_when`. The error names the field; add the value or make the field optional (ask first).
- **`update_field` merges `settings`**; send a setting with `null` to remove it.
- `create_field` with a `group` needs the group to exist; with `category_ids`, the categories (IDs, slugs or names); with `show_when`, the controlling fields; a `relationship` needs `settings.related_entity` to exist.
- `list_records` filters: `status`, `search` (title/slug), `category_id`, `filters` (a JSON **string** like `{"price": {"gte": 10}}`, operators `eq ne gt gte lt lte in contains null`), `sort` (`-price,title`), `limit` (max 100) and `offset`.
- `delete_record` soft-deletes unless `force: true`. `restore_record_revision` overwrites the current content but records a new revision, so it can be undone.
- Validation errors come back as readable messages (`price: The Price field must be a number.`). Fix the value and retry; never retry the identical call.

## Workflow: build a content model

1. `list_entities` — reuse or extend what exists; never create a duplicate model.
2. Propose the structure (see content-modeling.md) and get approval.
3. Create referenced entities first (`authors` before `posts`), so `relationship` fields can point to them.
4. `create_entity` with an explicit `slug`, a `description`, an `icon` (Heroicon name, e.g. `heroicon-o-document-text`).
5. `create_category` for each kind of entry; keep the returned IDs.
6. `create_field_group` for each form section (`is_collapsed: true` for SEO and other secondary sections).
7. `create_field` for each field in display order, with `group`, `category_ids`, `width`, `description`, `validation_rules` and `settings` as needed. Create controlling fields before fields with `show_when`.
8. `get_entity_schema` — check keys, types, groups and categories match the proposal.

## Workflow: create or migrate content

1. `get_entity_schema` and, if categories matter, `list_categories` (you need category IDs).
2. For each entry: `create_record` with `title`, explicit `slug` when the frontend fetches it by slug, `status: "draft"`, `category_ids`, and `field_values` following each `value_format`.
3. Relationship values are IDs: create or look up the related entries first (`list_records` with `search`).
4. `get_record` to confirm every value was stored.
5. Publish (`publish_record`) only when the user asked for it.
6. Report what was created: entity, title, slug, ID, status, and anything left for the user (images to upload in the panel, translations missing).

Prefer `set_record_values` for value-only changes: it never touches title, slug, status or categories.

## Workflow: change a model that has content

1. `get_entity_schema` and `list_records` (`limit: 5`) to see what is in use.
2. Explain the impact (which entries and frontends are affected) and ask before deleting or renaming anything.
3. Prefer additive changes; deactivate (`update_field` with `is_active: false`) instead of deleting when unsure.
4. Tell the user to regenerate types (`npx @flexacms/cli generate`) when keys or types changed.
