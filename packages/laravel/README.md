# flexacms/laravel

Laravel client for the [Flexa](https://github.com/flexacms) headless CMS: fluent queries, Laravel paginators, caching and translatable values.

> This repository is a read-only split of [flexacms/sdk](https://github.com/flexacms/sdk). Open issues and pull requests there.

```bash
composer require flexacms/laravel
```

```env
FLEXA_URL=https://cms.example.com
FLEXA_API_KEY=flx_...
```

```php
use FlexaCms\Laravel\Facades\Flexa;

$posts = Flexa::entity('posts')->published()->paginate(12); // LengthAwarePaginator of Entry

$post = Flexa::entity('posts')->findOrFail($slug); // 404 when missing

$post->title;
$post->value('headline');        // translatable values resolve to app()->getLocale()
$post->value('headline', 'en');
$post['rating'];
```

```blade
@foreach ($posts as $post)
    <a href="{{ route('blog.show', $post->slug) }}">{{ $post->value('headline') }}</a>
@endforeach

{{ $posts->links() }}
```

## Queries

`published()`, `status()`, `search()`, `category()` and `withRelations()` filter; `paginate()`, `all()`, `cursor()` (lazy), `find()`, `findOrFail()` and `schema()` read. Queries are immutable, so a base query can be shared.

Writes need a key with the Write or Delete ability: `create()`, `update()`, `delete($id, force: true)` and `restore()`. Failures throw `FlexaCms\Laravel\Exceptions\FlexaException` with `status` and validation `errors`.

## Cache

Reads are cached for 60 seconds (`FLEXA_CACHE_TTL`, `0` to disable) in the default store (`FLEXA_CACHE_STORE`). Per query: `->cache(3600)` or `->cache(0)`. Clear everything with `Flexa::flushCache()`.

```bash
php artisan vendor:publish --tag=flexa-config
```
