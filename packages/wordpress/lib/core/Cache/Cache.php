<?php

// Generated from flexacms/php by scripts/sync-php-core.php. Do not edit: change packages/php instead.

namespace FlexaCms\WordPress\Core\Cache;

/**
 * Stores read responses. Implement it with your platform's cache
 * (e.g. transients in WordPress).
 */
interface Cache
{
    public function get(string $key): mixed;

    public function set(string $key, mixed $value, int $seconds): void;
}
