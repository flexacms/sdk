# WordPress, Joomla and YOOtheme Pro

Read-only plugins that show Flexa content inside an existing CMS. They are installed from ZIPs in the SDK releases (not Composer or npm) and need a key with the **Read** ability. They never write content.

## WordPress (`flexa` plugin)

Configure in **Settings → Flexa** (URL + API key), or keep the key out of the database in `wp-config.php`:

```php
define( 'FLEXA_URL', 'https://cms.example.com' );
define( 'FLEXA_API_KEY', 'flx_...' );
```

| Feature            | Usage                                                                                                                                      |
| ------------------ | ------------------------------------------------------------------------------------------------------------------------------------------ |
| Blocks             | **Flexa entries** (grid or list of an entity) and **Flexa entry** (one entry or one field)                                                 |
| Shortcodes         | `[flexa_entries entity="posts" link="/post/?entry={slug}"]`, `[flexa_entry entity="pages" slug="about" field="headline"]`                  |
| Detail pages       | A page with `[flexa_entry entity="posts" slug_param="entry"]` reads the slug from `?entry=`                                                |
| Template functions | `flexa_entries( 'posts', array( 'per_page' => 3 ) )`, `flexa_entry()`, `flexa_value( $entry, 'headline' )`, `flexa_url( $entry, 'cover' )` |
| Templates          | Copy `templates/entries.php` or `templates/entry.php` to `your-theme/flexa/`                                                               |
| Filters            | `flexa_client_options`, `flexa_locales`, `flexa_template`                                                                                  |

Translatable fields follow WPML, Polylang or the site language. Responses are cached in transients (5 minutes by default); **Clear Flexa cache** in the settings page refreshes them.

## Joomla 5 (`pkg_flexa.zip`)

| Extension           | Provides                                                                                                                                                                        |
| ------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `com_flexa`         | Options (URL, API key, cache) and menu item types **Flexa: entries** (list with SEF URLs like `/blog/hello-world`) and **Flexa: single entry**                                  |
| `mod_flexa`         | **Flexa entries** module                                                                                                                                                        |
| `plg_content_flexa` | Tags in articles: `{flexa entity="pages" slug="about" field="headline"}`, `{flexa entity="pages" slug="about"}`, `{flexa entity="posts" list="3" image="cover" text="excerpt"}` |

Cached with Joomla's cache handler and translated to the site language.

## YOOtheme Pro (WordPress or Joomla)

Needs the platform plugin above plus `flexa-yootheme.zip` (WordPress) or `plg_system_flexayootheme.zip` (Joomla, enable it). Each content model becomes a dynamic content source in the builder's **Flexa** group:

- **Flexa <Entity>**: one entry, by slug or from the `?entry=` URL parameter (detail pages).
- **Flexa <Entity> (list)**: published entries with category, search, start and quantity, for grids and sliders.

Every field can be mapped: text, rich text (with the _limit_ filter), dates (_date_ filter), images and files as URLs (`*_all` gives every file of a `multiple` field), and repeaters as lists of items. Translatable fields follow the site language.

For anything these plugins do not cover (filters by field value, writes), use `flexacms/php` ([php.md](php.md)) from the theme or a custom plugin.
