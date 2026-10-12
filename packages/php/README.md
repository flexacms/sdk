# flexacms/php

Framework-agnostic PHP client for the [Flexa](https://github.com/flexacms) headless CMS. No dependencies; HTTP transport and cache are pluggable. Using Laravel? Try [`flexacms/laravel`](https://github.com/flexacms/laravel).

> Read-only split of [flexacms/sdk](https://github.com/flexacms/sdk). Open issues there.

```bash
composer require flexacms/php
```

```php
use FlexaCms\Client\Client;

$flexa = new Client('https://cms.example.com', getenv('FLEXA_API_KEY'), [
    'locale' => 'es',                 // translatable fields
    'fallback_locales' => ['en'],
    'cache_ttl' => 300,               // seconds; 0 disables
]);

$page = $flexa->entity('posts')->published()->list(page: 1, perPage: 12);

foreach ($page as $post) {
    echo $post->title, ' ', $post->value('headline'), ' ', $post->url('cover');
}

$about = $flexa->entity('pages')->find('about');   // null when missing
```

Queries are immutable: `published()`, `status()`, `search()`, `category()`, `withRelations()`, `cache($seconds)`; then `list()`, `cursor()`, `all()`, `find()`, `get()`, `schema()`, `create()`, `update()`, `delete()` and `restore()`. Failures throw `FlexaCms\Client\Exceptions\FlexaException` (`status`, validation `errors`). Reads are retried after rate limits and network errors; writes never are.

| Option                       | Default   |                                            |
| ---------------------------- | --------- | ------------------------------------------ |
| `api_path`                   | `/api/v1` |                                            |
| `timeout`                    | `30`      | seconds                                    |
| `retries`                    | `2`       | reads only                                 |
| `cache_ttl`                  | `60`      | seconds                                    |
| `cache`                      | in-memory | implement `FlexaCms\Client\Cache\Cache`    |
| `transport`                  | cURL      | implement `FlexaCms\Client\Http\Transport` |
| `locale`, `fallback_locales` | —         | for `Entry::value()` and `Entry::url()`    |
