<?php

namespace FlexaCms\Laravel;

use FlexaCms\Laravel\Exceptions\EntryNotFoundException;
use FlexaCms\Laravel\Exceptions\FlexaException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use Illuminate\Support\LazyCollection;

/**
 * Fluent queries for the entries of one entity. Every filter returns a new
 * query, so a base query can be shared safely.
 */
class EntityQuery
{
    /**
     * @var array<string, scalar>
     */
    protected array $filters = [];

    protected ?int $ttl = null;

    public function __construct(
        protected Flexa $flexa,
        protected string $entity,
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
     * Cache the results for this many seconds (0 to skip the cache).
     */
    public function cache(int $seconds): static
    {
        $query = clone $this;
        $query->ttl = $seconds;

        return $query;
    }

    /**
     * One page of entries, as a paginator that renders links like any other.
     *
     * @return LengthAwarePaginator<int, Entry>
     */
    public function paginate(int $perPage = 25, ?int $page = null): LengthAwarePaginator
    {
        $page ??= Paginator::resolveCurrentPage();
        $response = $this->flexa->get($this->path(), [...$this->filters, 'page' => $page, 'per_page' => $perPage], $this->ttl);
        $meta = $response['meta'] ?? [];

        return new LengthAwarePaginator(
            array_map(Entry::fromArray(...), $response['data'] ?? []),
            (int) ($meta['total'] ?? 0),
            (int) ($meta['per_page'] ?? $perPage),
            (int) ($meta['current_page'] ?? $page),
            ['path' => Paginator::resolveCurrentPath(), 'pageName' => 'page'],
        );
    }

    /**
     * Every matching entry, fetched page by page as you iterate.
     *
     * @return LazyCollection<int, Entry>
     */
    public function cursor(): LazyCollection
    {
        return LazyCollection::make(function () {
            $page = 1;

            do {
                $response = $this->flexa->get($this->path(), [...$this->filters, 'page' => $page, 'per_page' => 100], $this->ttl);

                foreach ($response['data'] ?? [] as $entry) {
                    yield Entry::fromArray($entry);
                }

                $lastPage = (int) ($response['meta']['last_page'] ?? 1);
                $page++;
            } while ($page <= $lastPage);
        });
    }

    /**
     * Every matching entry.
     *
     * @return Collection<int, Entry>
     */
    public function all(): Collection
    {
        return $this->cursor()->collect();
    }

    /**
     * An entry by id or slug, or null when it does not exist.
     */
    public function find(int|string $idOrSlug): ?Entry
    {
        try {
            $query = ($this->filters['include'] ?? null) === 'relations' ? ['include' => 'relations'] : [];

            return Entry::fromArray($this->flexa->get($this->path($idOrSlug), $query, $this->ttl)['data']);
        } catch (FlexaException $exception) {
            if ($exception->isNotFound()) {
                return null;
            }

            throw $exception;
        }
    }

    /**
     * An entry by id or slug. Missing entries become a 404 response.
     */
    public function findOrFail(int|string $idOrSlug): Entry
    {
        return $this->find($idOrSlug) ?? throw EntryNotFoundException::for($this->entity, $idOrSlug);
    }

    /**
     * Fields, groups, categories and locales of the entity.
     *
     * @return array<string, mixed>
     */
    public function schema(): array
    {
        return $this->flexa->get($this->path().'/schema', [], $this->ttl)['data'] ?? [];
    }

    /**
     * Create an entry. Needs an API key with the Write ability.
     *
     * @param  array{title?: string|null, slug?: string|null, status?: string, order?: int, categories?: array<int, int|string>, values?: array<string, mixed>}  $attributes
     */
    public function create(array $attributes): Entry
    {
        return Entry::fromArray($this->flexa->request('POST', $this->path(), body: $attributes)['data']);
    }

    /**
     * Partially update an entry. Needs an API key with the Write ability.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function update(int|string $idOrSlug, array $attributes): Entry
    {
        return Entry::fromArray($this->flexa->request('PATCH', $this->path($idOrSlug), body: $attributes)['data']);
    }

    /**
     * Trash an entry, or delete it for good with `$force`. Needs the Delete ability.
     */
    public function delete(int|string $idOrSlug, bool $force = false): void
    {
        $this->flexa->request('DELETE', $this->path($idOrSlug), $force ? ['force' => 1] : []);
    }

    /**
     * Restore a trashed entry. Needs the Write ability.
     */
    public function restore(int|string $idOrSlug): Entry
    {
        return Entry::fromArray($this->flexa->request('POST', $this->path($idOrSlug).'/restore')['data']);
    }

    protected function with(string $filter, string|int $value): static
    {
        $query = clone $this;
        $query->filters[$filter] = $value;

        return $query;
    }

    protected function path(int|string|null $idOrSlug = null): string
    {
        return '/'.rawurlencode($this->entity).($idOrSlug === null ? '' : '/'.rawurlencode((string) $idOrSlug));
    }
}
