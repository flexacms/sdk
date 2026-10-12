<?php

// Generated from flexacms/php by scripts/sync-php-core.php. Do not edit: change packages/php instead.

namespace FlexaCms\Joomla\Core\Cache;

/**
 * In-memory cache for the current request (the default).
 */
final class ArrayCache implements Cache
{
    /**
     * @var array<string, array{0: mixed, 1: int}>
     */
    private array $items = [];

    public function get(string $key): mixed
    {
        [$value, $expiresAt] = $this->items[$key] ?? [null, 0];

        return $expiresAt >= time() ? $value : null;
    }

    public function set(string $key, mixed $value, int $seconds): void
    {
        $this->items[$key] = [$value, time() + $seconds];
    }
}
