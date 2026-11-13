# Field types

Flexa ships exactly these 21 types; the `type` of a field in the schema is always one of them. Every write (panel, REST, MCP, import) goes through the same validator.

| Type           | Value to send (`value_format`)                                                                    | Translatable | Filterable (`filter[...]`)             | Notes                                                       |
| -------------- | ------------------------------------------------------------------------------------------------- | ------------ | -------------------------------------- | ----------------------------------------------------------- |
| `text`         | string, max 255                                                                                   | yes          | yes (translatable: `contains`, `null`) | Titles, labels, short copy                                  |
| `textarea`     | string                                                                                            | yes          | yes (translatable: `contains`, `null`) | Plain multi-line text                                       |
| `richtext`     | HTML string                                                                                       | yes          | `null` only                            | Long formatted copy                                         |
| `number`       | number                                                                                            | no           | yes, numeric                           | Prices, ratings, counts                                     |
| `boolean`      | `true` / `false`                                                                                  | no           | yes                                    | Flags (`featured`)                                          |
| `date`         | `"YYYY-MM-DD"`                                                                                    | no           | yes, as date                           |                                                             |
| `datetime`     | `"YYYY-MM-DD HH:MM:SS"`                                                                           | no           | yes                                    |                                                             |
| `email`        | email string                                                                                      | no           | yes                                    |                                                             |
| `url`          | absolute URL string                                                                               | no           | yes                                    | Use `link` for relative URLs or CTAs                        |
| `phone`        | string of digits, spaces, `+ - ( ) . # * x`, max 50                                               | no           | yes                                    | `phone_mask` setting                                        |
| `color`        | color string, e.g. `"#ff0000"`                                                                    | no           | yes                                    |                                                             |
| `select`       | one option **key**                                                                                | no           | yes                                    | Options map `{"key": "Label"}`                              |
| `multiselect`  | array of option keys                                                                              | no           | yes, by element                        |                                                             |
| `tags`         | array of strings                                                                                  | no           | yes, by element                        | Free-form labels                                            |
| `json`         | JSON object or array                                                                              | no           | `null` only                            | Last resort; editors cannot edit it comfortably             |
| `repeater`     | array of objects using the `repeater_subfields` keys                                              | no           | `null` only                            | Sending it replaces all rows                                |
| `address`      | object with the configured parts: `street`, `street_2`, `city`, `state`, `postal_code`, `country` | no           | `null` only                            | When required: `street`, `city`, `country`                  |
| `link`         | `{"url": "/contact", "title": "Contact", "target": "_self"}` (`_self` or `_blank`)                | no           | `null` only                            | URL may be relative or `#anchor`; title is not translatable |
| `relationship` | record ID of the related entity, or array of IDs when `multiple`                                  | no           | yes, by related ID                     | Related entries must exist in the same workspace            |
| `image`        | stored file path, or array of paths when `multiple`                                               | no           | `null` only                            | Paths must belong to the workspace; see Media               |
| `file`         | stored file path, or array of paths when `multiple`                                               | no           | `null` only                            | Same as `image`                                             |

## Field attributes

| Attribute                           | Meaning                                                                                                                 |
| ----------------------------------- | ----------------------------------------------------------------------------------------------------------------------- |
| `key`                               | snake_case machine key, unique in the entity. Generated from the label if omitted.                                      |
| `label`                             | Editor-facing name.                                                                                                     |
| `description`                       | Helper text under the input. Use it to tell editors what goes there.                                                    |
| `placeholder`, `default_value`      | Form hints / default for new entries.                                                                                   |
| `is_required`                       | Enforced on every API/MCP create, even when the field is hidden (see content-modeling.md).                              |
| `is_active`                         | `false` hides the field everywhere and keeps its values.                                                                |
| `is_searchable`, `is_shown_in_list` | Panel table: searchable column / visible column.                                                                        |
| `width`                             | `full` (default), `half`, `third`.                                                                                      |
| `options`                           | Map for `select` / `multiselect`: `{"in_stock": "In stock"}`. Send the key, not the label.                              |
| `validation_rules`                  | Laravel rule strings, e.g. `["min:3", "max:160", "alpha_dash"]`. `exists:` and `unique:` are rejected from the API/MCP. |
| `category_ids`                      | Categories the field is shown for (IDs, slugs or names when creating through MCP). Empty = every entry.                 |
| `group`                             | Key of the field group (section) the field belongs to.                                                                  |

## Settings (`settings` object)

| Setting          | Types                           | Meaning                                                                                                 |
| ---------------- | ------------------------------- | ------------------------------------------------------------------------------------------------------- |
| `translatable`   | `text`, `textarea`, `richtext`  | Store one value per workspace locale.                                                                   |
| `multiple`       | `relationship`, `image`, `file` | Allow several values (array).                                                                           |
| `related_entity` | `relationship` (required)       | Slug of the related entity. It must exist before the field is created.                                  |
| `schema`         | `repeater`                      | Sub-fields: array of `{label, key, type, width, is_required}`; `select` sub-fields also take `options`. |
| `show_when`      | any                             | `{"match": "all"\|"any", "conditions": [{"field": "kind", "operator": "equals", "value": "physical"}]}` |
| `phone_mask`     | `phone`                         | e.g. `"(999) 999-9999"` (`9` = digit).                                                                  |
| `address_parts`  | `address`                       | Subset of the address parts to show.                                                                    |

`show_when` operators: `equals`, `not_equals`, `in`, `not_in`, `contains`, `filled`, `blank`, `gt`, `gte`, `lt`, `lte`, `is_true`, `is_false`. Conditions must reference existing fields of the same entity (create the controlling field first) and never the field itself. Hidden fields are not validated in the panel, but are still validated by the API/MCP.

Keep repeater sub-fields simple (text, textarea, number, select, image, link, url, boolean). Do not nest repeaters and do not rely on `show_when` or translation inside repeater rows; if rows need those, make the rows their own entity and link them with a `relationship (multiple)`.

## Media

- `values` holds the stored path of each `image` / `file` (a path on the uploads disk inside the workspace's directory, e.g. `flex-fields/<workspace>/...`); `media` mirrors it with absolute URLs, including media sub-fields of repeaters. Frontends render from `media`; writes send paths back in `values`.
- Files are uploaded in the Flexa panel (the field itself or the Media Library). The REST API and MCP **cannot upload files**. An agent can only set paths that already exist in the workspace (for example, copied from another entry's `values`). When content needs new images, create the entry without them and tell the user to upload them in the panel.
- Alt text is not stored with the image: add a `text` field (`hero_image_alt`, translatable if needed) when accessibility or SEO needs it.

## Choosing between similar types

- `select` vs. categories: `select` for one fixed attribute the frontend branches on (`layout`, `availability`); categories when it classifies entries for filtering or switches a set of fields.
- `select` vs. `tags`: `select`/`multiselect` when the list is closed, `tags` when editors invent values.
- `url` vs. `link`: `url` for a bare absolute URL (`website`), `link` for a button or menu item (URL + title + target).
- `repeater` vs. `relationship`: repeater for rows that belong to one entry only; relationship for content with its own life or reused elsewhere.
- `richtext` vs. `textarea`: `textarea` when the frontend controls formatting, `richtext` when editors do.
- `json`: only for data editors never touch (integration payloads). Prefer typed fields.
