<?php

namespace FlexaCms\YOOtheme;

// Loaded by WordPress or Joomla only.
if ( ! defined( 'ABSPATH' ) ) {
    defined('_JEXEC') or exit;
}

use Throwable;

/**
 * Static resolvers referenced by the source types (YOOtheme caches the schema,
 * so resolvers cannot be closures). Entries are the Entry objects of the
 * platform's Flexa client.
 */
final class Resolver
{
    /**
     * Query: one entry by slug, or by the ?entry= URL parameter.
     *
     * @param  array{entity: string, slug?: string|null, from_url?: bool|null}  $args
     */
    public static function entry(mixed $root, array $args): ?object
    {
        $slug = (string) ($args['slug'] ?? '');

        // A public, read-only URL parameter (no state change), reduced to slug characters.
        if (! empty($args['from_url']) && isset($_GET['entry']) && is_string($_GET['entry'])) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput -- reduced to slug characters below.
            $slug = (string) preg_replace('/[^A-Za-z0-9_\-]/', '', $_GET['entry']); // phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput
        }

        $client = Connection::client();

        if ($client === null || $slug === '') {
            return null;
        }

        try {
            return $client->entity($args['entity'])->find($slug);
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * Query: published entries, with optional category and search filters.
     *
     * @param  array{entity: string, category?: int|null, search?: string|null, offset?: int|null, limit?: int|null}  $args
     * @return array<int, object>
     */
    public static function entries(mixed $root, array $args): array
    {
        $client = Connection::client();

        if ($client === null) {
            return [];
        }

        $offset = max(0, (int) ($args['offset'] ?? 0));
        $limit = max(1, min(100, (int) ($args['limit'] ?? 10)));
        $query = $client->entity($args['entity'])->published();

        if (! empty($args['category'])) {
            $query = $query->category((int) $args['category']);
        }

        if (! empty($args['search'])) {
            $query = $query->search((string) $args['search']);
        }

        try {
            $entries = [];
            $page = 1;

            do {
                $result = $query->list($page, 100);
                $entries = [...$entries, ...$result->items];
                $page++;
            } while (count($entries) < $offset + $limit && $result->hasMorePages());

            return array_slice($entries, $offset, $limit);
        } catch (Throwable) {
            return [];
        }
    }

    /**
     * Field values. `kind` says where the value comes from:
     * attribute, categories, value, media, media_list, repeater, item, self.
     *
     * @param  array{key: string, kind: string}  $args
     */
    public static function field(mixed $source, array $args): mixed
    {
        $key = $args['key'];

        return match ($args['kind']) {
            'attribute' => self::scalar($source->{$key} ?? null),
            'categories' => implode(', ', array_column((array) ($source->categories ?? []), 'name')),
            'value' => self::scalar($source->value($key)),
            'media' => self::firstUrl($source->url($key)),
            'media_list' => array_map(static fn (string $url): array => ['url' => $url], array_values(array_filter((array) $source->url($key), 'is_string'))),
            'repeater' => self::repeaterItems($source, $key),
            'item', 'self' => self::scalar(is_array($source) ? ($source[$key] ?? null) : ($source->{$key} ?? null)),
            default => null,
        };
    }

    /**
     * Repeater items with the URLs of their media sub-fields merged in.
     *
     * @return array<int, array<string, mixed>>
     */
    private static function repeaterItems(object $entry, string $key): array
    {
        $items = $entry->value($key);
        $media = $entry->media[$key] ?? [];

        if (! is_array($items)) {
            return [];
        }

        return array_values(array_map(static function (mixed $item, int $index) use ($media): array {
            $item = is_array($item) ? $item : [];

            foreach ((array) ($media[$index] ?? []) as $subKey => $url) {
                if ($url !== null) {
                    $item[$subKey] = $url;
                }
            }

            return $item;
        }, $items, array_keys($items)));
    }

    private static function firstUrl(mixed $url): ?string
    {
        if (is_array($url)) {
            $url = reset($url);
        }

        return is_string($url) ? $url : null;
    }

    /**
     * Builder fields are strings (or booleans): lists are joined, structures dropped.
     */
    private static function scalar(mixed $value): mixed
    {
        return match (true) {
            $value === null, is_string($value), is_bool($value) => $value,
            is_int($value), is_float($value) => (string) $value,
            is_array($value) && array_is_list($value) && array_filter($value, 'is_scalar') === $value => implode(', ', $value),
            default => null,
        };
    }
}
