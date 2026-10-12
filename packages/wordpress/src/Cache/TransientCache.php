<?php

namespace FlexaCms\WordPress\Cache;

use FlexaCms\WordPress\Core\Cache\Cache;

/**
 * Caches Flexa responses in WordPress transients (the object cache when one is installed).
 */
final class TransientCache implements Cache
{
    public function get(string $key): mixed
    {
        $value = get_transient($key);

        return $value === false ? null : $value;
    }

    public function set(string $key, mixed $value, int $seconds): void
    {
        set_transient($key, $value, $seconds);
    }
}
