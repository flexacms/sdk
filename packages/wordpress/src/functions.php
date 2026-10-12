<?php

/**
 * Template functions for themes and plugins.
 *
 *     $posts = flexa_entries('posts', ['per_page' => 3]);
 *     foreach ($posts as $post) {
 *         echo esc_html(flexa_value($post, 'headline'));
 *     }
 */

use FlexaCms\WordPress\Core\Client;
use FlexaCms\WordPress\Core\Entry;
use FlexaCms\WordPress\Core\Page;
use FlexaCms\WordPress\Plugin;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * The Flexa client, or null until the plugin is configured.
 */
function flexa(): ?Client
{
    return Plugin::client();
}

/**
 * A page of entries. Args: status (default "published"), category, search,
 * per_page, page, relations (bool).
 *
 * @param  array<string, mixed>  $args
 */
function flexa_entries(string $entity, array $args = []): Page
{
    $client = flexa();

    if ($client === null) {
        return new Page([], 0, 1, 1, 0);
    }

    $query = $client->entity($entity);
    $status = (string) ($args['status'] ?? 'published');

    if ($status !== '' && $status !== 'any') {
        $query = $query->status($status);
    }

    if (! empty($args['category'])) {
        $query = $query->category((int) $args['category']);
    }

    if (! empty($args['search'])) {
        $query = $query->search((string) $args['search']);
    }

    if (! empty($args['relations'])) {
        $query = $query->withRelations();
    }

    return $query->list(max(1, (int) ($args['page'] ?? 1)), max(1, min(100, (int) ($args['per_page'] ?? 10))));
}

/**
 * One entry by id or slug, or null when it does not exist.
 */
function flexa_entry(string $entity, int|string $idOrSlug): ?Entry
{
    return flexa()?->entity($entity)->find($idOrSlug);
}

/**
 * A field value, with translatable fields in the current language.
 */
function flexa_value(Entry $entry, string $key, mixed $default = null): mixed
{
    return $entry->value($key, null, $default);
}

/**
 * Public URL(s) of an image or file field.
 *
 * @return string|array<int, string>|null
 */
function flexa_url(Entry $entry, string $key): string|array|null
{
    return $entry->url($key);
}
