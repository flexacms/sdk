<?php

use FlexaCms\Client\Client;
use FlexaCms\Client\Tests\FakeTransport;

require_once __DIR__.'/FakeTransport.php';

/**
 * A client wired to a fake transport that never sleeps.
 *
 * @param  array<string, mixed>  $options
 */
function flexaClient(FakeTransport $transport, array $options = []): Client
{
    return new Client('https://cms.test/', 'flx_test', [
        'transport' => $transport,
        'sleep' => fn (int $milliseconds) => $GLOBALS['slept'][] = $milliseconds,
        ...$options,
    ]);
}

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function flexaEntry(int $id = 1, array $overrides = []): array
{
    return [
        'id' => $id,
        'entity_slug' => 'posts',
        'title' => "Post {$id}",
        'slug' => "post-{$id}",
        'status' => 'published',
        'categories' => [],
        'values' => ['headline' => ['en' => 'Hello', 'es' => 'Hola'], 'cover' => 'flex-fields/a/cover.webp'],
        'media' => ['cover' => 'https://media.test/cover.webp'],
        'created_at' => '2026-10-01T10:00:00+00:00',
        'updated_at' => '2026-10-02T10:00:00+00:00',
        ...$overrides,
    ];
}

/**
 * @return array<string, mixed>
 */
function flexaPage(array $ids, int $page = 1, int $lastPage = 1): array
{
    return [
        'data' => array_map(fn (int $id): array => flexaEntry($id), $ids),
        'meta' => ['current_page' => $page, 'last_page' => $lastPage, 'per_page' => 25, 'total' => count($ids)],
    ];
}
