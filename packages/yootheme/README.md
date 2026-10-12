# Flexa for YOOtheme Pro

Use Flexa content models as **dynamic content sources** in the YOOtheme Pro page builder, on WordPress and Joomla.

> Read-only split of [flexacms/sdk](https://github.com/flexacms/sdk). Download the ZIPs from the releases.

Each Flexa content model becomes a source in the builder's **Flexa** group:

- **Flexa Posts** — one entry, by slug or from the `?entry=` URL parameter (for detail pages).
- **Flexa Posts (list)** — published entries with category, search, start and quantity, for grids, sliders and other multiple-items elements.

Every field can be mapped: text, rich text (with the _limit_ filter), dates (with the _date_ filter), images and files as URLs (`*_all` gives every file of a multiple field), and repeaters as lists of items. Translatable fields follow the site language.

The integration reuses the connection of the Flexa plugin for your platform:

| Platform  | Install                                                      | Configure          |
| --------- | ------------------------------------------------------------ | ------------------ |
| WordPress | `flexa` plugin + `flexa-yootheme.zip`                        | Settings → Flexa   |
| Joomla    | `pkg_flexa.zip` + `plg_system_flexayootheme.zip` (enable it) | Components → Flexa |

Build both ZIPs with `php scripts/build-yootheme.php` (from `sdk/`).
