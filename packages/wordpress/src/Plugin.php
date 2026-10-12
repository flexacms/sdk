<?php

namespace FlexaCms\WordPress;

use FlexaCms\WordPress\Cache\TransientCache;
use FlexaCms\WordPress\Core\Client;
use FlexaCms\WordPress\Http\WordPressTransport;

/**
 * Wires the plugin into WordPress.
 */
final class Plugin
{
    private static ?Client $client = null;

    public static function boot(): void
    {
        Settings::register();
        Shortcodes::register();
        Blocks::register();
        Rest::register();

        // Registered on init (before the blocks) so block.json can use it in the editor too.
        add_action('init', [self::class, 'styles'], 5);
        add_action('update_option_'.Settings::OPTION, static function (): void {
            self::$client = null;
        });
    }

    /**
     * The configured Flexa client, or null until the URL and API key are set.
     */
    public static function client(): ?Client
    {
        if (self::$client !== null) {
            return self::$client;
        }

        if (! Settings::isConfigured()) {
            return null;
        }

        $settings = Settings::all();
        $locales = Locale::candidates();

        /**
         * Filters the options of the Flexa client (timeout, retries, headers...).
         *
         * @param  array<string, mixed>  $options
         */
        $options = (array) apply_filters('flexa_client_options', [
            'transport' => new WordPressTransport,
            'cache' => new TransientCache,
            'cache_ttl' => $settings['cache_ttl'],
            'timeout' => 10,
            'retries' => 1,
            'locale' => $locales[0] ?? null,
            'fallback_locales' => array_slice($locales, 1),
            'headers' => ['X-Flexa-Client' => 'wordpress/'.FLEXA_VERSION],
        ]);

        return self::$client = new Client($settings['url'], $settings['api_key'], $options);
    }

    public static function styles(): void
    {
        wp_register_style('flexa', FLEXA_URL_PATH.'assets/flexa.css', [], FLEXA_VERSION);
    }
}
