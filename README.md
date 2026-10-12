# Flexa SDKs

Official SDKs for the Flexa content API, developed together in this monorepo and published as independent packages.

| Package                                     | For                    |                                          |
| ------------------------------------------- | ---------------------- | ---------------------------------------- |
| [`@flexacms/client`](packages/client)       | Any JavaScript runtime | Typed core client, no dependencies       |
| [`@flexacms/cli`](packages/cli)             | TypeScript projects    | Generates types from your content models |
| [`@flexacms/react`](packages/react)         | React                  | Provider and hooks                       |
| [`@flexacms/next`](packages/next)           | Next.js (App Router)   | Cached server fetching, tag revalidation |
| [`@flexacms/nuxt`](packages/nuxt)           | Nuxt 3/4               | Module, composables, read-only proxy     |
| [`@flexacms/astro`](packages/astro)         | Astro 5+               | Content collection loader                |
| [`@flexacms/sveltekit`](packages/sveltekit) | SvelteKit              | Server hook with `event.locals.flexa`    |
| [`flexacms/laravel`](packages/laravel)      | Laravel 12–13          | Facade, fluent queries, caching          |

Every framework package re-exports `@flexacms/client`, so one install is enough.

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

All packages share one version, like Laravel's components. The app repository is private, so the SDK source is published through read-only mirrors: `sdk/` is split into the public **flexacms/sdk** repository (linked from npm, where issues live) and `sdk/packages/laravel` into **flexacms/laravel** (for Packagist).

```bash
git tag sdk-v0.2.0 && git push origin sdk-v0.2.0
```

The `sdk-release` workflow sets that version on every package (`scripts/set-version.mjs`), runs the checks, publishes the npm packages and pushes both splits with a `v0.2.0` tag.

Before the first release:

1. Create the public `flexacms/sdk` and `flexacms/laravel` repositories on GitHub (empty), and a token that can push to both → `SPLIT_TOKEN` secret.
2. An npm automation token with publish rights on the `@flexacms` scope → `NPM_TOKEN` secret.
3. Register `flexacms/laravel` on Packagist.

npm provenance is not enabled: npm only issues it for builds from public repositories.
