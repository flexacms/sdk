<?php

use FlexaCms\Client\Cache\ArrayCache;
use FlexaCms\Client\Client;
use FlexaCms\Client\Exceptions\FlexaException;
use FlexaCms\Client\Http\TransportException;
use FlexaCms\Client\Tests\FakeTransport;

beforeEach(function () {
    $GLOBALS['slept'] = [];
});

test('the url and api key are required', function () {
    expect(fn () => new Client('', 'k'))->toThrow(InvalidArgumentException::class, 'URL')
        ->and(fn () => new Client('https://cms.test', ' '))->toThrow(InvalidArgumentException::class, 'API key');
});

test('requests carry the api key and json headers', function () {
    $transport = new FakeTransport([FakeTransport::json(['data' => [['slug' => 'posts']]])]);

    expect(flexaClient($transport, ['headers' => ['X-Site' => 'blog']])->entities())->toBe([['slug' => 'posts']]);

    $request = $transport->requests[0];
    expect($request->url)->toBe('https://cms.test/api/v1/entities')
        ->and($request->headers)->toMatchArray(['Authorization' => 'Bearer flx_test', 'Accept' => 'application/json', 'X-Site' => 'blog'])
        ->and($request->headers['User-Agent'])->toStartWith('flexacms-php/');
});

test('list sends filters and returns a page of entries', function () {
    $transport = new FakeTransport([FakeTransport::json(flexaPage([1, 2], page: 2, lastPage: 3))]);

    $page = flexaClient($transport)->entity('posts')->published()->search('hello world')->category(3)->withRelations()->list(2, 10);

    expect($page->items)->toHaveCount(2)
        ->and($page->currentPage)->toBe(2)
        ->and($page->hasMorePages())->toBeTrue()
        ->and(count($page))->toBe(2)
        ->and($transport->requests[0]->url)->toBe('https://cms.test/api/v1/posts?category=3&include=relations&page=2&per_page=10&search=hello%20world&status=published');
});

test('all() walks every page', function () {
    $transport = new FakeTransport([FakeTransport::json(flexaPage([1, 2], 1, 2)), FakeTransport::json(flexaPage([3], 2, 2))]);

    expect(array_map(fn ($entry) => $entry->id, flexaClient($transport)->entity('posts')->all()))->toBe([1, 2, 3]);
});

test('entries resolve translations and file urls', function () {
    $transport = new FakeTransport([FakeTransport::json(['data' => flexaEntry(5, ['media' => [
        'cover' => 'https://media.test/cover.webp',
        'brochure' => ['en' => 'https://media.test/en.pdf', 'es' => 'https://media.test/es.pdf'],
    ]])])]);

    $entry = flexaClient($transport, ['locale' => 'es', 'fallback_locales' => ['en']])->entity('posts')->find('post-5');

    expect($entry->value('headline'))->toBe('Hola')
        ->and($entry->value('headline', 'fr'))->toBe('Hello')
        ->and($entry['cover'])->toBe('flex-fields/a/cover.webp')
        ->and($entry->url('cover'))->toBe('https://media.test/cover.webp')
        ->and($entry->url('brochure'))->toBe('https://media.test/es.pdf')
        ->and($entry->url('missing'))->toBeNull()
        ->and($entry->value('missing', default: 'n/a'))->toBe('n/a');
});

test('find() returns null for missing entries but get() throws', function () {
    $transport = new FakeTransport([
        FakeTransport::json(['message' => 'Record [x] not found.'], 404),
        FakeTransport::json(['message' => 'Record [x] not found.'], 404),
    ]);
    $posts = flexaClient($transport, ['cache_ttl' => 0])->entity('posts');

    expect($posts->find('x'))->toBeNull()
        ->and(fn () => $posts->get('x'))->toThrow(FlexaException::class, 'Record [x] not found.');
});

test('reads are cached until the cache is flushed', function () {
    $transport = new FakeTransport(array_fill(0, 3, FakeTransport::json(['data' => flexaEntry()])));
    $client = flexaClient($transport, ['cache' => new ArrayCache]);

    $client->entity('posts')->find(1);
    $client->entity('posts')->find(1);
    expect($transport->requests)->toHaveCount(1);

    $client->flushCache();
    $client->entity('posts')->find(1);
    $client->entity('posts')->cache(0)->find(1);
    expect($transport->requests)->toHaveCount(3);
});

test('writes send json bodies and are never cached', function () {
    $transport = new FakeTransport(array_fill(0, 4, FakeTransport::json(['data' => flexaEntry()])));
    $posts = flexaClient($transport)->entity('posts');

    $posts->create(['title' => 'Hello', 'values' => ['rating' => 4]]);
    $posts->update(1, ['status' => 'archived']);
    $posts->delete(1, force: true);
    $posts->restore(1);

    expect(array_map(fn ($request) => "{$request->method} {$request->url}", $transport->requests))->toBe([
        'POST https://cms.test/api/v1/posts',
        'PATCH https://cms.test/api/v1/posts/1',
        'DELETE https://cms.test/api/v1/posts/1?force=1',
        'POST https://cms.test/api/v1/posts/1/restore',
    ])->and(json_decode($transport->requests[0]->body, true))->toBe(['title' => 'Hello', 'values' => ['rating' => 4]]);
});

test('validation errors are exposed on the exception', function () {
    $transport = new FakeTransport([FakeTransport::json(['message' => 'The title field is required.', 'errors' => ['title' => ['The title field is required.']]], 422)]);

    try {
        flexaClient($transport)->entity('posts')->create([]);
        $this->fail('Expected a FlexaException.');
    } catch (FlexaException $exception) {
        expect($exception->status)->toBe(422)->and($exception->errors)->toBe(['title' => ['The title field is required.']]);
    }
});

test('reads are retried after rate limits and network errors, writes are not', function () {
    $transport = new FakeTransport([
        FakeTransport::json(['message' => 'Too Many Attempts.'], 429, ['retry-after' => '2']),
        new TransportException('Connection refused'),
        FakeTransport::json(['data' => []]),
        FakeTransport::json(['message' => 'Service Unavailable'], 503),
    ]);
    $client = flexaClient($transport, ['cache_ttl' => 0]);

    expect($client->entities())->toBe([])
        ->and($GLOBALS['slept'])->toBe([2000, 500]);

    expect(fn () => $client->entity('posts')->create(['title' => 'x']))->toThrow(FlexaException::class, 'Service Unavailable');
    expect($transport->requests)->toHaveCount(4);
});

test('network failures become a FlexaException with status 0', function () {
    $transport = new FakeTransport([new TransportException('Could not resolve host')]);

    try {
        flexaClient($transport, ['retries' => 0])->entities();
        $this->fail('Expected a FlexaException.');
    } catch (FlexaException $exception) {
        expect($exception->status)->toBe(0)->and($exception->getMessage())->toBe('Flexa request failed: Could not resolve host');
    }
});
