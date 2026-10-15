<?php

use FlexaCms\Laravel\Entry;
use FlexaCms\Laravel\Exceptions\EntryNotFoundException;
use FlexaCms\Laravel\Exceptions\FlexaException;
use FlexaCms\Laravel\Facades\Flexa;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Sleep;

beforeEach(function () {
    Sleep::fake();
});

test('requests are authenticated with the api key', function () {
    Http::fake(['cms.test/*' => Http::response(['data' => [['slug' => 'posts']]])]);

    expect(Flexa::entities())->toBe([['slug' => 'posts']]);

    Http::assertSent(fn (Request $request): bool => $request->url() === 'https://cms.test/api/v1/entities'
        && $request->hasHeader('Authorization', 'Bearer flx_test')
        && $request->hasHeader('Accept', 'application/json'));
});

test('filters are sent as query parameters and results are paginated', function () {
    Http::fake(['cms.test/*' => Http::response(flexaPage([1, 2], total: 30))]);

    $page = Flexa::entity('posts')->published()->search('hello world')->category(3)->withRelations()->paginate(perPage: 10, page: 2);

    expect($page->total())->toBe(30)
        ->and($page->items())->each->toBeInstanceOf(Entry::class)
        ->and($page->first()->title)->toBe('Post 1');

    Http::assertSent(fn (Request $request): bool => $request->url() === 'https://cms.test/api/v1/posts?category=3&include=relations&page=2&per_page=10&search=hello%20world&status=published');
});

test('queries are immutable so a base query can be reused', function () {
    Http::fake(['cms.test/*' => Http::response(flexaPage([1]))]);
    $posts = Flexa::entity('posts');

    $posts->published()->all();
    $posts->all();

    Http::assertSent(fn (Request $request): bool => str_contains($request->url(), 'status=published'));
    Http::assertSent(fn (Request $request): bool => ! str_contains($request->url(), 'status='));
});

test('all() walks every page', function () {
    Http::fake(['cms.test/*' => Http::sequence()
        ->push(flexaPage([1, 2], page: 1, lastPage: 2))
        ->push(flexaPage([3], page: 2, lastPage: 2))]);

    expect(Flexa::entity('posts')->all()->pluck('id')->all())->toBe([1, 2, 3]);
    Http::assertSentCount(2);
});

test('entries resolve translatable values to the app locale', function () {
    Http::fake(['cms.test/*' => Http::response(['data' => flexaEntry(7)])]);
    app()->setLocale('es');

    $entry = Flexa::entity('posts')->find('post-7');

    expect($entry->value('headline'))->toBe('Hola')
        ->and($entry->value('headline', 'en'))->toBe('Hello')
        ->and($entry->value('headline', 'fr'))->toBe('Hello')
        ->and($entry['rating'])->toBe(5)
        ->and($entry->value('missing', default: 'n/a'))->toBe('n/a')
        ->and($entry->createdAt?->toDateString())->toBe('2026-10-01')
        ->and($entry->toArray()['slug'])->toBe('post-7');
});

test('missing entries are null from find() and a 404 from findOrFail()', function () {
    Http::fake(['cms.test/*' => Http::response(['message' => 'Record [nope] not found.'], 404)]);

    expect(Flexa::entity('posts')->find('nope'))->toBeNull();
    expect(fn () => Flexa::entity('posts')->findOrFail('nope'))->toThrow(EntryNotFoundException::class);
});

test('reads are cached until the cache is flushed', function () {
    Http::fake(['cms.test/*' => Http::response(['data' => flexaEntry()])]);

    Flexa::entity('posts')->find(1);
    Flexa::entity('posts')->find(1);
    Http::assertSentCount(1);

    Flexa::flushCache();
    Flexa::entity('posts')->find(1);
    Http::assertSentCount(2);

    Flexa::entity('posts')->cache(0)->find(1);
    Http::assertSentCount(3);
});

test('writes send json and are never cached', function () {
    Http::fake(['cms.test/*' => Http::response(['data' => flexaEntry()])]);
    $posts = Flexa::entity('posts');

    $posts->create(['title' => 'Hello', 'values' => ['rating' => 4]]);
    $posts->update(1, ['status' => 'archived']);
    $posts->delete(1, force: true);
    $posts->restore(1);

    $sent = Http::recorded()->map(fn (array $pair): string => $pair[0]->method().' '.$pair[0]->url())->all();

    expect($sent)->toBe([
        'POST https://cms.test/api/v1/posts',
        'PATCH https://cms.test/api/v1/posts/1',
        'DELETE https://cms.test/api/v1/posts/1?force=1',
        'POST https://cms.test/api/v1/posts/1/restore',
    ]);
    Http::assertSent(fn (Request $request): bool => $request->method() === 'POST' && ($request->data()['values'] ?? null) === ['rating' => 4]);
});

test('validation errors are exposed on the exception', function () {
    Http::fake(['cms.test/*' => Http::response(['message' => 'The title field is required.', 'errors' => ['title' => ['The title field is required.']]], 422)]);

    try {
        Flexa::entity('posts')->create([]);
        $this->fail('Expected a FlexaException.');
    } catch (FlexaException $exception) {
        expect($exception->status)->toBe(422)
            ->and($exception->getMessage())->toBe('The title field is required.')
            ->and($exception->errors)->toBe(['title' => ['The title field is required.']]);
    }
});

test('reads are retried after a rate limit but writes are not', function () {
    Http::fake(['cms.test/*' => Http::sequence()
        ->push(['message' => 'Too Many Attempts.'], 429, ['Retry-After' => '2'])
        ->push(['data' => []])
        ->push(['message' => 'Service Unavailable'], 503)]);

    expect(Flexa::entities())->toBe([]);
    Sleep::assertSlept(fn ($duration): bool => $duration->totalMilliseconds === 2000.0, times: 1);

    expect(fn () => Flexa::entity('posts')->create(['title' => 'x']))->toThrow(FlexaException::class, 'Service Unavailable');
    Http::assertSentCount(3);
});

test('a missing configuration is explained', function () {
    config(['flexa.api_key' => null]);
    app()->forgetInstance(FlexaCms\Laravel\Flexa::class);
    Flexa::clearResolvedInstances();

    expect(fn () => Flexa::entities())->toThrow(InvalidArgumentException::class, 'FLEXA_API_KEY');
});

test('entries expose the public urls of their files', function () {
    Http::fake(['cms.test/*' => Http::response(['data' => flexaEntry(8, [
        'media' => [
            'cover' => 'https://media.test/cover.webp',
            'gallery' => ['https://media.test/1.webp', 'https://media.test/2.webp'],
            'brochure' => ['en' => 'https://media.test/en.pdf', 'es' => 'https://media.test/es.pdf'],
        ],
    ])])]);
    app()->setLocale('es');

    $entry = Flexa::entity('posts')->find(8);

    expect($entry->url('cover'))->toBe('https://media.test/cover.webp')
        ->and($entry->url('gallery'))->toBe(['https://media.test/1.webp', 'https://media.test/2.webp'])
        ->and($entry->url('brochure'))->toBe('https://media.test/es.pdf')
        ->and($entry->url('missing'))->toBeNull();
});

test('the url defaults to flexacms.com when FLEXA_URL is not set', function () {
    putenv('FLEXA_URL');

    $config = require __DIR__.'/../../config/flexa.php';

    expect($config['url'])->toBe('https://flexacms.com');
});
