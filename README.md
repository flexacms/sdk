# Flexa SDKs

Official SDKs for the Flexa content API, developed together in this monorepo and published as independent packages.

| Package                                     | For                             |                                                          |
| ------------------------------------------- | ------------------------------- | -------------------------------------------------------- |
| [`@flexacms/client`](packages/client)       | Any JavaScript runtime          | Typed core client, no dependencies                       |
| [`@flexacms/cli`](packages/cli)             | TypeScript projects             | Generates types from your content models                 |
| [`@flexacms/react`](packages/react)         | React                           | Provider and hooks                                       |
| [`@flexacms/next`](packages/next)           | Next.js (App Router)            | Cached server fetching, tag revalidation                 |
| [`@flexacms/nuxt`](packages/nuxt)           | Nuxt 3/4                        | Module, composables, read-only proxy                     |
| [`@flexacms/astro`](packages/astro)         | Astro 5+                        | Content collection loader                                |
| [`@flexacms/sveltekit`](packages/sveltekit) | SvelteKit                       | Server hook with `event.locals.flexa`                    |
| [`flexacms/php`](packages/php)              | Any PHP 8.1+ app                | Framework-agnostic client, pluggable transport and cache |
| [`flexacms/laravel`](packages/laravel)      | Laravel 12–13                   | Facade, fluent queries, caching                          |
| [WordPress plugin](packages/wordpress)      | WordPress 6.5+                  | Blocks, shortcodes, template functions                   |
| [Joomla package](packages/joomla)           | Joomla 5                        | Menu items with SEF URLs, module, `{flexa}` article tags |
| [YOOtheme Pro](packages/yootheme)           | YOOtheme Pro (WordPress/Joomla) | Dynamic content sources for the page builder             |

Every JavaScript framework package re-exports `@flexacms/client`, so one install is enough. The CMS plugins bundle `flexacms/php` under their own namespace (`scripts/sync-php-core.php`) so they never conflict with each other.

## Development

```bash
cd sdk
npm install
npm run check          # build, typecheck and test every JavaScript package

cd packages/laravel
composer install
vendor/bin/pest
```

The JavaScript packages build with tsup and are tested with Vitest; the Laravel package uses Pest and Orchestra Testbench. `sdk/` has its own `package.json`, so the SDK tooling never touches the CMS app's dependencies.

## Releasing

All packages share one version, like Laravel's components. Bump it everywhere (npm packages, PHP client, plugin headers and manifests), commit, and tag that commit:

```bash
cd sdk && node scripts/set-version.mjs 0.3.0
git commit -am "Release SDK 0.3.0"
git tag sdk-v0.3.0 && git push origin main sdk-v0.3.0
```

The `sdk-release` workflow checks that the committed version matches the tag, runs the checks, publishes the npm packages, splits `sdk/`, `packages/laravel` and `packages/php` into the public read-only repositories **flexacms/sdk**, **flexacms/laravel** and **flexacms/php** (Packagist picks up the `v0.3.0` tags), and attaches the plugin ZIPs to a **flexacms/sdk** GitHub release:

| ZIP                                                  | Built by                          |
| ---------------------------------------------------- | --------------------------------- |
| `flexa.zip` (WordPress)                              | `php scripts/build-wordpress.php` |
| `pkg_flexa.zip` (Joomla)                             | `php scripts/build-joomla.php`    |
| `flexa-yootheme.zip`, `plg_system_flexayootheme.zip` | `php scripts/build-yootheme.php`  |

Secrets on this repository: `NPM_TOKEN` (publish on `@flexacms`) and `SPLIT_TOKEN` (Contents: read and write on flexacms/sdk, flexacms/laravel and flexacms/php).
