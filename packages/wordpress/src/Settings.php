<?php

namespace FlexaCms\WordPress;

use FlexaCms\WordPress\Core\Exceptions\FlexaException;

/**
 * Settings → Flexa: connection, cache and a live connection check.
 *
 * The URL and API key can also be defined in wp-config.php (FLEXA_URL,
 * FLEXA_API_KEY), which keeps the key out of the database.
 */
final class Settings
{
    public const OPTION = 'flexa_settings';

    private const PAGE = 'flexa';

    /**
     * @return array{url: string, api_key: string, cache_ttl: int}
     */
    public static function all(): array
    {
        $saved = (array) get_option(self::OPTION, []);

        return [
            'url' => defined('FLEXA_URL') ? (string) constant('FLEXA_URL') : (string) ($saved['url'] ?? ''),
            'api_key' => defined('FLEXA_API_KEY') ? (string) constant('FLEXA_API_KEY') : (string) ($saved['api_key'] ?? ''),
            'cache_ttl' => (int) ($saved['cache_ttl'] ?? 300),
        ];
    }

    public static function isConfigured(): bool
    {
        $settings = self::all();

        return $settings['url'] !== '' && $settings['api_key'] !== '';
    }

    public static function register(): void
    {
        add_action('admin_menu', [self::class, 'menu']);
        add_action('admin_init', [self::class, 'fields']);
        add_action('admin_post_flexa_clear_cache', [self::class, 'clearCache']);
        add_action('admin_notices', [self::class, 'notice']);
        add_filter('plugin_action_links_'.plugin_basename(FLEXA_FILE), [self::class, 'actionLinks']);
    }

    public static function menu(): void
    {
        add_options_page(__('Flexa', 'flexa'), __('Flexa', 'flexa'), 'manage_options', self::PAGE, [self::class, 'render']);
    }

    public static function fields(): void
    {
        register_setting(self::PAGE, self::OPTION, [
            'type' => 'array',
            'sanitize_callback' => [self::class, 'sanitize'],
            'default' => ['url' => '', 'api_key' => '', 'cache_ttl' => 300],
        ]);

        add_settings_section('flexa_connection', __('Connection', 'flexa'), static function (): void {
            echo '<p>'.esc_html__('Use an API key with only the Read ability, limited to the content models this site shows.', 'flexa').'</p>';
        }, self::PAGE);

        add_settings_field('flexa_url', __('Flexa URL', 'flexa'), [self::class, 'urlField'], self::PAGE, 'flexa_connection', ['label_for' => 'flexa_url']);
        add_settings_field('flexa_api_key', __('API key', 'flexa'), [self::class, 'apiKeyField'], self::PAGE, 'flexa_connection', ['label_for' => 'flexa_api_key']);
        add_settings_field('flexa_cache_ttl', __('Cache', 'flexa'), [self::class, 'cacheField'], self::PAGE, 'flexa_connection', ['label_for' => 'flexa_cache_ttl']);
    }

    /**
     * @param  mixed  $input
     * @return array{url: string, api_key: string, cache_ttl: int}
     */
    public static function sanitize($input): array
    {
        $input = is_array($input) ? $input : [];
        $saved = (array) get_option(self::OPTION, []);
        $apiKey = trim((string) ($input['api_key'] ?? ''));

        Plugin::client()?->flushCache();

        return [
            'url' => esc_url_raw(trim((string) ($input['url'] ?? ''))),
            // An empty field keeps the saved key, so it never has to be shown again.
            'api_key' => $apiKey !== '' ? sanitize_text_field($apiKey) : (string) ($saved['api_key'] ?? ''),
            'cache_ttl' => max(0, min(86400, (int) ($input['cache_ttl'] ?? 300))),
        ];
    }

    public static function urlField(): void
    {
        $settings = self::all();

        printf(
            '<input type="url" id="flexa_url" name="%s[url]" value="%s" class="regular-text" placeholder="https://cms.example.com" %s />',
            esc_attr(self::OPTION),
            esc_attr($settings['url']),
            defined('FLEXA_URL') ? 'readonly' : '',
        );

        if (defined('FLEXA_URL')) {
            echo '<p class="description">'.esc_html__('Defined in wp-config.php (FLEXA_URL).', 'flexa').'</p>';
        }
    }

    public static function apiKeyField(): void
    {
        $settings = self::all();

        if (defined('FLEXA_API_KEY')) {
            echo '<input type="password" id="flexa_api_key" value="••••••••" class="regular-text" readonly />';
            echo '<p class="description">'.esc_html__('Defined in wp-config.php (FLEXA_API_KEY).', 'flexa').'</p>';

            return;
        }

        printf(
            '<input type="password" id="flexa_api_key" name="%s[api_key]" value="" class="regular-text" autocomplete="new-password" placeholder="%s" />',
            esc_attr(self::OPTION),
            esc_attr($settings['api_key'] !== '' ? __('Saved — leave empty to keep it', 'flexa') : 'flx_…'),
        );
        echo '<p class="description">'.esc_html__('Tip: define FLEXA_API_KEY in wp-config.php to keep the key out of the database.', 'flexa').'</p>';
    }

    public static function cacheField(): void
    {
        $settings = self::all();

        printf(
            '<input type="number" id="flexa_cache_ttl" name="%s[cache_ttl]" value="%d" min="0" max="86400" step="1" class="small-text" /> %s',
            esc_attr(self::OPTION),
            (int) $settings['cache_ttl'],
            esc_html__('seconds (0 disables the cache)', 'flexa'),
        );
    }

    public static function render(): void
    {
        if (! current_user_can('manage_options')) {
            return;
        }

        echo '<div class="wrap"><h1>'.esc_html__('Flexa', 'flexa').'</h1>';

        if (isset($_GET['flexa-cache-cleared'])) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            echo '<div class="notice notice-success is-dismissible"><p>'.esc_html__('Flexa cache cleared.', 'flexa').'</p></div>';
        }

        echo '<form method="post" action="options.php">';
        settings_fields(self::PAGE);
        do_settings_sections(self::PAGE);
        submit_button();
        echo '</form>';

        self::renderStatus();

        echo '</div>';
    }

    private static function renderStatus(): void
    {
        echo '<h2>'.esc_html__('Status', 'flexa').'</h2>';

        $client = Plugin::client();

        if ($client === null) {
            echo '<p>'.esc_html__('Enter the Flexa URL and an API key to connect.', 'flexa').'</p>';

            return;
        }

        try {
            $entities = $client->entities();
        } catch (FlexaException $exception) {
            printf(
                '<div class="notice notice-error inline"><p><strong>%s</strong> %s</p></div>',
                esc_html__('Could not connect:', 'flexa'),
                esc_html($exception->getMessage()),
            );

            return;
        }

        printf(
            '<div class="notice notice-success inline"><p>%s</p></div>',
            esc_html(sprintf(
                /* translators: %d: number of content models */
                _n('Connected. %d content model available:', 'Connected. %d content models available:', count($entities), 'flexa'),
                count($entities),
            )),
        );

        echo '<table class="widefat striped" style="max-width:720px"><thead><tr><th>'.esc_html__('Content model', 'flexa').'</th><th>'.esc_html__('Slug', 'flexa').'</th><th>'.esc_html__('Example', 'flexa').'</th></tr></thead><tbody>';

        foreach ($entities as $entity) {
            printf(
                '<tr><td>%s</td><td><code>%s</code></td><td><code>[flexa_entries entity="%s"]</code></td></tr>',
                esc_html((string) ($entity['name'] ?? '')),
                esc_html((string) ($entity['slug'] ?? '')),
                esc_attr((string) ($entity['slug'] ?? '')),
            );
        }

        echo '</tbody></table>';

        printf(
            '<form method="post" action="%s" style="margin-top:1em">%s<input type="hidden" name="action" value="flexa_clear_cache" />%s</form>',
            esc_url(admin_url('admin-post.php')),
            wp_nonce_field('flexa_clear_cache', '_wpnonce', true, false), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            get_submit_button(__('Clear Flexa cache', 'flexa'), 'secondary', 'submit', false), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        );
    }

    public static function clearCache(): void
    {
        if (! current_user_can('manage_options')) {
            wp_die(esc_html__('You are not allowed to do this.', 'flexa'), 403);
        }

        check_admin_referer('flexa_clear_cache');
        Plugin::client()?->flushCache();

        wp_safe_redirect(add_query_arg('flexa-cache-cleared', '1', admin_url('options-general.php?page='.self::PAGE)));
        exit;
    }

    public static function notice(): void
    {
        $screen = function_exists('get_current_screen') ? get_current_screen() : null;

        if (self::isConfigured() || ! current_user_can('manage_options') || ($screen && $screen->id === 'settings_page_'.self::PAGE)) {
            return;
        }

        printf(
            '<div class="notice notice-warning"><p>%s <a href="%s">%s</a></p></div>',
            esc_html__('Flexa is not connected yet.', 'flexa'),
            esc_url(admin_url('options-general.php?page='.self::PAGE)),
            esc_html__('Add your Flexa URL and API key', 'flexa'),
        );
    }

    /**
     * @param  array<int|string, string>  $links
     * @return array<int|string, string>
     */
    public static function actionLinks(array $links): array
    {
        array_unshift($links, sprintf('<a href="%s">%s</a>', esc_url(admin_url('options-general.php?page='.self::PAGE)), esc_html__('Settings', 'flexa')));

        return $links;
    }
}
