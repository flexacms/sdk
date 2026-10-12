<?php

// Generated from flexacms/php by scripts/sync-php-core.php. Do not edit: change packages/php instead.

namespace FlexaCms\WordPress\Core;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use Traversable;

/**
 * One page of entries.
 *
 * @implements IteratorAggregate<int, Entry>
 */
final class Page implements Countable, IteratorAggregate
{
    /**
     * @param  array<int, Entry>  $items
     */
    public function __construct(
        public readonly array $items,
        public readonly int $total,
        public readonly int $currentPage,
        public readonly int $lastPage,
        public readonly int $perPage,
    ) {}

    public function hasMorePages(): bool
    {
        return $this->currentPage < $this->lastPage;
    }

    public function count(): int
    {
        return count($this->items);
    }

    /**
     * @return Traversable<int, Entry>
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->items);
    }
}
