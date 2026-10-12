<?php

namespace FlexaCms\WordPress;

use FlexaCms\WordPress\Core\Exceptions\FlexaException;
use WP_Error;
use WP_REST_Response;

/**
 * GET /wp-json/flexa/v1/entities — content models and their fields, for the
 * block editor. Editors only: the API key itself is never exposed.
 */
final class Rest
{
    public static function register(): void
    {
        add_action('rest_api_init', static function (): void {
            register_rest_route('flexa/v1', '/entities', [
                'methods' => 'GET',
                'callback' => [self::class, 'entities'],
                'permission_callback' => static fn (): bool => current_user_can('edit_posts'),
            ]);
        });
    }

    public static function entities(): WP_REST_Response|WP_Error
    {
        $client = Plugin::client();

        if ($client === null) {
            return new WP_Error('flexa_not_configured', __('Flexa is not connected. Add the URL and API key in Settings → Flexa.', 'flexa'), ['status' => 503]);
        }

        try {
            $entities = array_map(static function (array $entity) use ($client): array {
                $schema = $client->entity((string) $entity['slug'])->schema();

                return [
                    'slug' => (string) $entity['slug'],
                    'name' => (string) ($entity['name'] ?? $entity['slug']),
                    'fields' => array_values(array_map(static fn (array $field): array => [
                        'key' => (string) $field['key'],
                        'label' => (string) ($field['label'] ?? $field['key']),
                        'type' => (string) ($field['type'] ?? 'text'),
                    ], $schema['fields'] ?? [])),
                ];
            }, $client->entities());
        } catch (FlexaException $exception) {
            return new WP_Error('flexa_request_failed', $exception->getMessage(), ['status' => 502]);
        }

        return new WP_REST_Response($entities);
    }
}
