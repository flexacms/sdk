# Flexa for WordPress

Show content from the Flexa headless CMS in WordPress: blocks, shortcodes and template functions, cached in transients and translated with WPML or Polylang.

> Read-only split of [flexacms/sdk](https://github.com/flexacms/sdk). Download the plugin ZIP from the releases.

See [readme.txt](readme.txt) for usage. Developers:

- `lib/core` is generated from `packages/php` (`php scripts/sync-php-core.php packages/wordpress/lib/core 'FlexaCms\WordPress\Core'`) so it can coexist with other plugins bundling the Flexa client.
- Theme overrides: copy `templates/entries.php` or `templates/entry.php` to `your-theme/flexa/`.
- Filters: `flexa_client_options`, `flexa_locales`, `flexa_template`.
