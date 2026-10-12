<?php

// Generated from flexacms/php by scripts/sync-php-core.php. Do not edit: change packages/php instead.

namespace FlexaCms\WordPress\Core;

use FlexaCms\WordPress\Core\Exceptions\FlexaException;
use Generator;

/**
 * Queries for the entries of one entity. Filters return a new query, so a
 * base query can be shared safely.
 */
class EntityQuery
{
    /**
     * @var array<string, scalar>
     */
    private array $filters = [];

    private ?int $ttl = null;

    public function __construct(
        private readonly Client $client,
        private readonly string $entity,
    ) {}

    public function status(string $status): static
    {
        return $this->with('status', $status);
    }

    public function published(): static
    {
        return $this->status('published');
    }

    public function search(string $term): static
    {
        return $this->with('search', $term);
    }

    public function category(int $categoryId): static
    {
        return $this->with('category', $categoryId);
    }

    /**
     * Expand relationship fields into `$entry->relations`.
     */
    public function withRelations(): static
    {
        return $this->with('include', 'relations');
    }

    /**
     * Cache results for this many seconds (0 to skip the cache).
     */
    public function cache(int $seconds): static
    {
        $query = clone $this;
        $query->ttl = $seconds;

        return $query;
    }

    /**
     * One page of entries.
     */
    public function list(int $page = 1, int $perPage = 25): Page
    {
        $response = $this->client->get($this->path(), [...$this->filters, 'page' => $page, 'per_page' => $perPage], $this->ttl);
        $meta = $response['meta'] ?? [];

        return new Page(
            array_map($this->client->makeEntry(...), $response['data'] ?? []),
            (int) ($meta['total'] ?? 0),
            (int) ($meta['current_page'] ?? $page),
            (int) ($meta['last_page'] ?? 1),
            (int) ($meta['per_page'] ?? $perPage),
        );
    }

    /**
     * Every matching entry, fetched page by page as you iterate.
     *
     * @return Generator<int, Entry>
     */
    public function cursor(): Generator
    {
        $page = 1;

        do {
            $result = $this->list($page, 100);

            yield from $result->items;

            $page++;
        } while ($page <= $result->lastPage);
    }

    /**
     * Every matching entry.
     *
     * @return array<int, Entry>
     */
    public function all(): array
    {
        return iterator_to_array($this->cursor(), false);
    }

    /**
     * An entry by id or slug, or null when it does not exist.
     */
    public function find(int|string $idOrSlug): ?Entry
    {
        try {
            return $this->get($idOrSlug);
        } catch (FlexaException $exception) {
            if ($exception->isNotFound()) {
                return null;
            }

            throw $exception;
        }
    }

    /**
     * An entry by id or slug. Throws a 404 FlexaException when it does not exist.
     */
    public function get(int|string $idOrSlug): Entry
    {
        $query = ($this->filters['include'] ?? null) === 'relations' ? ['include' => 'relations'] : [];

        return $this->client->makeEntry($this->client->get($this->path($idOrSlug), $query, $this->ttl)['data'] ?? []);
    }

    /**
     * Fields, groups, categories and locales of the entity.
     *
     * @return array<string, mixed>
     */
    public function schema(): array
    {
        return $this->client->get($this->path().'/schema', [], $this->ttl)['data'] ?? [];
    }

    /**
     * Create an entry. Needs an API key with the Write ability.
     *
     * @param  array<string, mixed>  $attributes  title, slug, status, order, categories, values
     */
    public function create(array $attributes): Entry
    {
        return $this->client->makeEntry($this->client->request('POST', $this->path(), body: $attributes)['data'] ?? []);
    }

    /**
     * Partially update an entry. Needs the Write ability.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function update(int|string $idOrSlug, array $attributes): Entry
    {
        return $this->client->makeEntry($this->client->request('PATCH', $this->path($idOrSlug), body: $attributes)['data'] ?? []);
    }

    /**
     * Trash an entry, or delete it for good with `$force`. Needs the Delete ability.
     */
    public function delete(int|string $idOrSlug, bool $force = false): void
    {
        $this->client->request('DELETE', $this->path($idOrSlug), $force ? ['force' => 1] : []);
    }

    /**
     * Restore a trashed entry. Needs the Write ability.
     */
    public function restore(int|string $idOrSlug): Entry
    {
        return $this->client->makeEntry($this->client->request('POST', $this->path($idOrSlug).'/restore')['data'] ?? []);
    }

    private function with(string $filter, string|int $value): static
    {
        $query = clone $this;
        $query->filters[$filter] = $value;

        return $query;
    }

    private function path(int|string|null $idOrSlug = null): string
    {
        return '/'.rawurlencode($this->entity).($idOrSlug === null ? '' : '/'.rawurlencode((string) $idOrSlug));
    }
}
