# Content modeling in Flexa

How to turn a website or app into Flexa entities, fields, groups and categories, and how to propose that structure to the user. Read this before creating or changing any model.

## The building blocks and what each one is for

| Need                                                                                                              | Use                                                     | Not                                                 |
| ----------------------------------------------------------------------------------------------------------------- | ------------------------------------------------------- | --------------------------------------------------- |
| A kind of thing there are many of (posts, products, people)                                                       | An **entity**                                           | A repeater inside a page                            |
| Different kinds of entries in one model with different fields (home vs. about page, physical vs. digital product) | **Categories** + fields scoped to them (`category_ids`) | One entity per page or per kind                     |
| Organizing a long form into sections (Hero, SEO, Pricing)                                                         | **Field groups**                                        | Categories (they decide visibility, not layout)     |
| A field that only makes sense after another field's value                                                         | `show_when` (conditional visibility)                    | Separate entities                                   |
| A short, ordered list owned by one entry (FAQ on one page, variants, stats)                                       | A **repeater**                                          | A separate entity                                   |
| Content reused in several places, or that editors manage on its own (testimonials, team, authors)                 | Its own **entity** + a `relationship` field             | Copying it into repeaters                           |
| Classifying entries for filtering (blog topics, product lines)                                                    | **Categories** (`?category=` filter)                    | A `select` field, unless it must be one fixed value |
| Free-form labels                                                                                                  | A `tags` field                                          | Categories                                          |
| One-off global content (site settings, header, footer)                                                            | One entry with a fixed slug (e.g. `settings/global`)    | Waiting for a "singleton" feature                   |

## Categories: one entity, many kinds of entry

This is the pattern most often missed. **You do not need an entity for every page or section of a site.** Group them in one entity and let categories decide which fields each entry shows.

How it works (verified behavior):

- Each entity has its own categories (hierarchical, with `parent_id`). Entries are assigned one or more categories.
- A field with `category_ids` is shown in the entry form **only when the entry belongs to at least one of those categories**. A field without categories is shown for every entry.
- A field group is shown when at least one of its fields is visible, so a section scoped to "Home" disappears from the "Contact" form.
- Category matching is **exact**: a field scoped to a parent category is not shown for an entry assigned only to a child. Assign the field to every category that needs it, or assign the entry to both parent and child.
- Visibility is a form concern. The API returns every stored value of the entry, and does not filter values by category.

### Example: a marketing site

One `pages` entity instead of `home`, `about`, `contact`, `services` entities:

```
Entity: pages  (Pages)
  Categories (page types): home, about, contact, landing
  Groups and fields:
    Hero            hero_title (text, translatable)       → all pages
                    hero_subtitle (textarea, translatable) → all pages
                    hero_image (image)                     → all pages
    Home            stats (repeater: value, label)         → [home]
                    featured_products (relationship → products, multiple) → [home]
    About           story (richtext, translatable)         → [about]
                    team (relationship → team-members, multiple) → [about]
    Contact         address (address)                      → [contact]
                    phone (phone), contact_email (email)   → [contact]
    Sections        sections (repeater: title, body, layout select) → [landing]
    SEO (collapsed) seo_title (text, max:60), seo_description (textarea, max:160), og_image (image) → all pages

Entries: one per page, with the slug the frontend routes on
  home (category home), about (category about), contact (category contact), pricing (category landing)
```

Frontend: `flexa.entity('pages').get('home')` returns that entry's values; the page component decides how to render by its category or slug.

Reuse a field across categories when it means the same thing (`hero_title` for all pages). Create a separate field when the meaning differs (`story` only on About). Keys are unique per entity, so a scoped field still needs a distinct key.

The same pattern applies outside pages: `products` with categories `physical` / `digital` and `weight` scoped to `physical`; `events` with `online` / `in-person` and `venue` scoped to `in-person`.

### Categories or `show_when`?

- Use **categories** when the kind is a classification you also want to filter by in the API (`?category=<id>`), or when it selects a whole set of fields.
- Use **`show_when`** when visibility depends on a field value inside the form (`cta_type = external` shows `cta_url`). It can combine with categories.

## When a separate entity is the right call

Create a new entity when at least one is true:

- There are (or will be) many entries of that kind, listed or paginated (posts, products, jobs).
- The content is reused by several entries (an author on many posts, a testimonial on several pages) → link it with a `relationship` field.
- Editors manage it on its own, with its own status, history or API key restriction.
- The frontend needs to filter or sort it by field values (`?filter[price][gte]=10`). Repeater contents cannot be filtered.

Otherwise prefer fields, groups, categories and repeaters in an existing entity. Fewer entities means fewer API calls and a simpler editor experience.

## Global content

There are no singleton entities. Model global content as one entity with a single entry whose slug never changes:

```
Entity: settings
  Groups: Brand (site_name, logo), Header (nav: repeater label + link), Footer (footer_text translatable, social: repeater platform select + url), Defaults (default_og_image)
Entry: slug "global"   →   flexa.entity('settings').get('global')
```

If header, footer and settings are edited by different people or change at different rates, use one entity with categories (`header`, `footer`, `settings`) and one entry each.

## Multilingual content

- Only `text`, `textarea` and `richtext` can be translatable. The workspace's locales come from the schema (`locales`); the first is the fallback and is the one required when the field is required.
- `link` titles, `select` labels, images and repeater rows are not translated. For a multilingual call to action use a translatable `text` label plus a `link` or `url` field. For multilingual repeated items that need translation, prefer a separate entity with translatable fields linked by a `relationship (multiple)`.
- `?locale=es` resolves top-level translatable fields to one locale. Values inside repeaters are returned as stored.
- Do not translate slugs per locale: Flexa has one slug per entry.

## Naming conventions

- **Entity slugs:** plural, lowercase, kebab-case, stable: `posts`, `team-members`, `case-studies`. The slug is part of every API URL.
- **Field keys:** snake_case, descriptive, stable: `hero_title`, `seo_description`, `published_on`. Avoid generic keys (`text1`, `image`, `data`).
- **Category slugs:** short and stable (`home`, `physical`); frontends may branch on them.
- **Entry slugs:** set them explicitly for entries the frontend fetches by slug (`home`, `global`). An auto-generated slug changes with the title and gets a suffix when it collides.
- Labels are for editors and can be in the user's language; keys and slugs stay in English-like snake/kebab case unless the user's project already uses another convention.

## Required fields and validation

- `is_required` is enforced on every create through the API and MCP, **including fields hidden by categories or `show_when`** (the panel only validates visible fields). Do not mark category-scoped or conditional fields as required, or entries of other categories cannot be created through the API.
- Put limits in `validation_rules` (`max:160`, `min:1`, `regex:...`). `exists:` and `unique:` rules are rejected from the API and MCP.
- Prefer `select` with a fixed option list over free text when the frontend branches on the value.

## Things to decide (the checklist humans forget)

Go through this list when proposing a structure and ask about whatever the user has not said:

1. **Pages vs. collections:** which content is one-off (pages, settings) and which repeats (posts, products)?
2. **Kinds inside a model:** which entries need different fields → categories with scoped fields.
3. **Reuse and relations:** what is shown in more than one place → its own entity + `relationship`.
4. **Languages:** which locales, and which fields must be translatable.
5. **SEO:** `seo_title`, `seo_description`, `og_image` (collapsed `SEO` group) on every routable entity.
6. **Media:** which images are single vs. galleries (`multiple`); alt text needs its own `text` field.
7. **Navigation:** header and footer menus (repeater of `link`, or translatable `text` + `link`).
8. **Ordering:** manual order for FAQs, testimonials, team (entries have an `order` attribute; sort with `sort=order`) vs. date order.
9. **Publishing:** drafts, scheduled publishing, previews, and whether the site must rebuild on change (webhooks).
10. **Listing and filters:** which lists the frontend shows, filtered or sorted by which fields (only top-level fields can be filtered).
11. **API access:** which entities the website key needs (a Read key restricted to them).
12. **Editor experience:** groups for long forms, `width` (`half`, `third`) for short fields, `description` helper texts, `is_shown_in_list` for the columns editors scan, `is_searchable` for lookup.

## Proposing a structure

Before creating anything, present the proposal and wait for approval. Use this shape:

```markdown
### Entity `pages` — Pages of the site

Purpose: one entry per page; categories select the fields of each page type.
Categories: home, about, contact, landing
| Group | Key | Type | Required | Translatable | Categories | Notes |
| Hero | hero_title | text | yes | yes | all | max:80 |
| Home | featured_products | relationship → products (multiple) | no | — | home | |
...
Entries to create: home, about, contact (draft)
Frontend: `entity('pages').get(slug)`; menus from `settings/global`
```

Then list, briefly: the relationships between entities, the open questions, and the order you will create things in. Keep the proposal minimal; extra entities and fields can be added later without breaking anything, while removing or renaming them can.

## Changing an existing model safely

- Read `list_entities` and the schema first; reuse what exists instead of creating a parallel model.
- Adding fields, groups and categories is safe. Renaming a field `key`, an entity slug or a category slug breaks frontends and typed code (regenerate types with `@flexacms/cli`).
- Deleting a field deletes its stored values; deleting an entity deletes its fields, categories and entries. Deleting a group keeps its fields. Deactivating a field (`is_active: false`) hides it and keeps the values, so prefer it when unsure.
- Changing a field's type on a field that already has values can leave values that no longer validate. Create a new field and migrate the values instead.
- Content model changes send the `schema.updated` webhook (debounced per entity) to webhooks subscribed to it, so typed frontends can regenerate types or rebuild.
