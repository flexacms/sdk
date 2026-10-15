# flexacms/php

Framework-agnostic PHP 8.1+ client with no dependencies. HTTP transport and cache are pluggable. For Laravel, use [laravel.md](laravel.md) instead.

```bash
composer require flexacms/php
```

```php
use FlexaCms\Client\Client;

$flexa = new Client('https://cms.example.com', getenv('FLEXA_API_KEY'), [
    'locale' => 'es',            // translatable fields
    'fallback_locales' => ['en'],
    'cache_ttl' => 300,          // seconds; 0 disables
]);

$page = $flexa->entity('posts')->published()->list(page: 1, perPage: 12);

foreach ($page as $post) {
    echo $post->title, ' ', $post->value('headline'), ' ', $post->url('cover');
}

$about = $flexa->entity('pages')->find('about'); // null when missing
```

## Queries

Immutable. Filters: `published()`, `status()`, `search()`, `category()`, `withRelations()`, `cache($seconds)`.

Terminal calls: `list()`, `cursor()`, `all()`, `find()`, `get()`, `schema()`, `create()`, `update()`, `delete()`, `restore()`.

Failures throw `FlexaCms\Client\Exceptions\FlexaException` with `status` and validation `errors`. Reads retry after rate limits and network errors. Writes never retry.

## Options

| Option | Default | Notes |
| --- | --- | --- |
| `api_path` | `/api/v1` | |
| `timeout` | `30` | Seconds |
| `retries` | `2` | Reads only |
| `cache_ttl` | `60` | Seconds |
| `cache` | in-memory | Implement `FlexaCms\Client\Cache\Cache` |
| `transport` | cURL | Implement `FlexaCms\Client\Http\Transport` |
| `locale`, `fallback_locales` | none | Used by `Entry::value()` and `Entry::url()` |
