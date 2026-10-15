# flexacms/laravel

Laravel 12–13 client: a facade, fluent immutable queries, Laravel paginators, caching and translatable values. Requires PHP 8.2+.

```bash
composer require flexacms/laravel
php artisan vendor:publish --tag=flexa-config   # optional
```

```env
FLEXA_API_KEY=flx_...
# FLEXA_URL=https://staging.example.com        # optional, defaults to https://flexacms.com
# FLEXA_CACHE_TTL=60                            # seconds, 0 disables
# FLEXA_CACHE_STORE=redis                       # default cache store if unset
```

```php
use FlexaCms\Laravel\Facades\Flexa;

$posts = Flexa::entity('posts')->published()->paginate(12); // LengthAwarePaginator of Entry

$post = Flexa::entity('posts')->findOrFail($slug);           // 404 when missing

$post->title;
$post->value('headline');          // translatable values resolve to app()->getLocale()
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

Filters: `published()`, `status()`, `search()`, `category()`, `withRelations()`.

Reads: `paginate()`, `all()`, `cursor()` (lazy), `find()`, `findOrFail()`, `schema()`.

Queries are immutable, so a base query can be stored and reused.

## Writes

`create()`, `update()`, `delete($id, force: true)` and `restore()` need a key with the Write or Delete ability. Failures throw `FlexaCms\Laravel\Exceptions\FlexaException` with `status` and validation `errors`.

## Cache

Reads are cached for 60 seconds in the default store. Per query: `->cache(3600)` or `->cache(0)`. Clear everything with `Flexa::flushCache()`.
