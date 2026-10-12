<?php

namespace FlexaCms\Joomla\Cache;

use FlexaCms\Joomla\Core\Cache\Cache;
use Joomla\CMS\Cache\CacheControllerFactoryInterface;
use Joomla\CMS\Cache\Controller\OutputController;
use Joomla\CMS\Factory;

\defined('_JEXEC') or exit;

/**
 * Caches Flexa responses with Joomla's cache handler (file, Redis...), even
 * when page caching is off. Each item carries its own expiry.
 */
final class JoomlaCache implements Cache
{
    private ?OutputController $controller = null;

    public function get(string $key): mixed
    {
        $item = $this->controller()->get($key);

        if (! is_array($item) || ($item['expires'] ?? 0) < time()) {
            return null;
        }

        return $item['value'] ?? null;
    }

    public function set(string $key, mixed $value, int $seconds): void
    {
        $this->controller()->store(['expires' => time() + $seconds, 'value' => $value], $key);
    }

    private function controller(): OutputController
    {
        return $this->controller ??= Factory::getContainer()
            ->get(CacheControllerFactoryInterface::class)
            ->createCacheController('output', [
                'defaultgroup' => 'com_flexa',
                'caching' => true,
                // Upper bound; items expire on their own before that.
                'lifetime' => 60 * 24 * 365,
            ]);
    }
}
