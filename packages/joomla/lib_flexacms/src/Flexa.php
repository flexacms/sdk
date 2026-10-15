<?php

namespace FlexaCms\Joomla;

use FlexaCms\Joomla\Cache\JoomlaCache;
use FlexaCms\Joomla\Core\Client;
use FlexaCms\Joomla\Http\JoomlaTransport;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;

\defined('_JEXEC') or exit;

/**
 * Entry point for Flexa in Joomla: the client configured in
 * System → Global Configuration → Flexa (com_flexa options).
 */
final class Flexa
{
    public const VERSION = '0.3.0';

    private static ?Client $client = null;

    /**
     * The configured client, or null until the URL and API key are set.
     */
    public static function client(): ?Client
    {
        if (self::$client !== null) {
            return self::$client;
        }

        $params = ComponentHelper::getParams('com_flexa');
        $url = trim((string) $params->get('url', ''));
        $apiKey = trim((string) $params->get('api_key', ''));

        if ($url === '' || $apiKey === '') {
            return null;
        }

        $locales = self::locales();

        return self::$client = new Client($url, $apiKey, [
            'transport' => new JoomlaTransport,
            'cache' => new JoomlaCache,
            'cache_ttl' => max(0, (int) $params->get('cache_ttl', 300)),
            'timeout' => 10,
            'retries' => 1,
            'locale' => $locales[0] ?? null,
            'fallback_locales' => array_slice($locales, 1),
            'headers' => ['X-Flexa-Client' => 'joomla/'.self::VERSION],
        ]);
    }

    /**
     * Forget the client, e.g. after the options change.
     */
    public static function reset(): void
    {
        self::$client = null;
    }

    /**
     * Locales for translatable fields, in order of preference: the current
     * language (es_ES, es), the site default language, then English.
     *
     * @return array<int, string>
     */
    public static function locales(): array
    {
        $current = str_replace('-', '_', Factory::getApplication()->getLanguage()->getTag());
        $default = str_replace('-', '_', (string) ComponentHelper::getParams('com_languages')->get('site', 'en-GB'));

        return array_values(array_unique([$current, strtok($current, '_'), $default, strtok($default, '_'), 'en']));
    }
}
