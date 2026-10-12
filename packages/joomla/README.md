# Flexa for Joomla

Show content from the Flexa headless CMS in Joomla 5: list and entry pages with SEF URLs, a module and `{flexa}` article tags. Cached with Joomla's cache handler and translated to the site language.

> Read-only split of [flexacms/sdk](https://github.com/flexacms/sdk). Download `pkg_flexa.zip` from the releases.

The package installs:

| Extension | |
| --- | --- |
| `lib_flexacms` | The Flexa PHP client (generated from `packages/php` under `FlexaCms\Joomla\Core`) with Joomla HTTP and cache adapters |
| `com_flexa` | Options (URL, API key, cache) with a connection dashboard; menu item types **Flexa: entries** (list, `/blog/hello-world` per entry) and **Flexa: single entry** |
| `mod_flexa` | **Flexa entries** module |
| `plg_content_flexa` | `{flexa entity="pages" slug="about" field="headline"}`, `{flexa entity="pages" slug="about"}`, `{flexa entity="posts" list="3" image="cover" text="excerpt"}` |

Build the installable ZIP with `php scripts/build-joomla.php` (from `sdk/`); it lands in `packages/joomla/build/`.
