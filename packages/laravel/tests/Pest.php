<?php

use FlexaCms\Laravel\Tests\TestCase;

pest()->extend(TestCase::class)->in('Feature');

/**
 * An entry as the Flexa API returns it.
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function flexaEntry(int $id = 1, array $overrides = []): array
{
    return [
        'id' => $id,
        'entity_id' => 1,
        'entity_slug' => 'posts',
        'title' => "Post {$id}",
        'slug' => "post-{$id}",
        'status' => 'published',
        'order' => null,
        'categories' => [],
        'values' => ['headline' => ['en' => 'Hello', 'es' => 'Hola'], 'rating' => 5],
        'created_at' => '2026-10-01T10:00:00+00:00',
        'updated_at' => '2026-10-02T10:00:00+00:00',
        'deleted_at' => null,
        ...$overrides,
    ];
}

/**
 * A page of entries as the Flexa API returns it.
 *
 * @param  array<int, int>  $ids
 * @return array<string, mixed>
 */
function flexaPage(array $ids, int $page = 1, int $lastPage = 1, int $total = 0): array
{
    return [
        'data' => array_map(fn (int $id): array => flexaEntry($id), $ids),
        'meta' => ['current_page' => $page, 'last_page' => $lastPage, 'per_page' => 25, 'total' => $total ?: count($ids)],
        'links' => [],
    ];
}
