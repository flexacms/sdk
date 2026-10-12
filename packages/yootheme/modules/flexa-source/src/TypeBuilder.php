<?php

namespace FlexaCms\YOOtheme;

// Loaded by WordPress or Joomla only.
if ( ! defined( 'ABSPATH' ) ) {
    defined('_JEXEC') or exit;
}


/**
 * Turns Flexa content models into YOOtheme Pro source types: one object type
 * per model (with every field mappable in the builder) and two queries, a
 * single entry and a list. Pure data: no YOOtheme or CMS calls, so it is
 * testable on its own.
 */
final class TypeBuilder
{
    public const GROUP = 'Flexa';

    private const MEDIA_TYPES = ['image', 'file'];

    /**
     * @param  array<int, array<string, mixed>>  $schemas  Flexa entity schemas (GET /{entity}/schema).
     * @return array{objectTypes: array<string, array<string, mixed>>, queryTypes: array<int, array<string, mixed>>}
     */
    public static function build(array $schemas): array
    {
        $objectTypes = [
            'FlexaFile' => [
                'fields' => [
                    'url' => self::field('String', 'URL', ['key' => 'url', 'kind' => 'self']),
                ],
                'metadata' => ['type' => true, 'label' => 'Flexa file'],
            ],
        ];
        $queryFields = [];

        foreach ($schemas as $schema) {
            $slug = (string) $schema['slug'];
            $name = (string) ($schema['name'] ?? $slug);
            $type = self::typeName($slug);

            $objectTypes[$type] = [
                'fields' => [
                    ...self::entryFields(),
                    ...self::customFields($type, (array) ($schema['fields'] ?? []), $objectTypes),
                ],
                'metadata' => ['type' => true, 'label' => "Flexa {$name}"],
            ];

            $queryFields[lcfirst($type)] = self::singleQuery($type, $slug, $name);
            $queryFields[lcfirst($type).'List'] = self::listQuery($type, $slug, $name);
        }

        return [
            'objectTypes' => $objectTypes,
            'queryTypes' => $queryFields === [] ? [] : [['fields' => $queryFields]],
        ];
    }

    /**
     * "blog-posts" → "FlexaBlogPosts".
     */
    public static function typeName(string $slug): string
    {
        $words = preg_split('/[^A-Za-z0-9]+/', $slug, -1, PREG_SPLIT_NO_EMPTY) ?: ['Entity'];
        $name = 'Flexa'.implode('', array_map(static fn (string $word): string => ucfirst(strtolower($word)), $words));

        return preg_match('/^Flexa[0-9]/', $name) ? 'FlexaEntity'.substr($name, 5) : $name;
    }

    /**
     * Fields every entry has.
     *
     * @return array<string, array<string, mixed>>
     */
    private static function entryFields(): array
    {
        return [
            'title' => self::field('String', 'Title', ['key' => 'title', 'kind' => 'attribute']),
            'slug' => self::field('String', 'Slug', ['key' => 'slug', 'kind' => 'attribute']),
            'id' => self::field('String', 'ID', ['key' => 'id', 'kind' => 'attribute']),
            'created_at' => self::field('String', 'Created', ['key' => 'createdAt', 'kind' => 'attribute'], ['filters' => ['date']]),
            'updated_at' => self::field('String', 'Updated', ['key' => 'updatedAt', 'kind' => 'attribute'], ['filters' => ['date']]),
            'categories' => self::field('String', 'Categories', ['key' => 'categories', 'kind' => 'categories']),
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $fields
     * @param  array<string, array<string, mixed>>  $objectTypes  Receives the repeater item types.
     * @return array<string, array<string, mixed>>
     */
    private static function customFields(string $type, array $fields, array &$objectTypes): array
    {
        $config = [];

        foreach ($fields as $field) {
            if (($field['is_active'] ?? true) === false) {
                continue;
            }

            $key = (string) $field['key'];
            $name = self::fieldName($key);
            $label = (string) ($field['label'] ?? $key);
            $fieldType = (string) ($field['type'] ?? 'text');

            if (in_array($fieldType, self::MEDIA_TYPES, true)) {
                $config[$name] = self::field('String', $label, ['key' => $key, 'kind' => 'media']);

                if (! empty($field['multiple'])) {
                    $config[$name.'_all'] = self::field(['listOf' => 'FlexaFile'], "{$label} (all)", ['key' => $key, 'kind' => 'media_list']);
                }

                continue;
            }

            if ($fieldType === 'repeater') {
                $itemType = $type.self::pascal($key).'Item';
                $objectTypes[$itemType] = self::repeaterItemType($itemType, $label, (array) ($field['repeater_subfields'] ?? []));
                $config[$name] = self::field(['listOf' => $itemType], $label, ['key' => $key, 'kind' => 'repeater']);

                continue;
            }

            $config[$name] = match ($fieldType) {
                'boolean' => self::field('Boolean', $label, ['key' => $key, 'kind' => 'value']),
                'date', 'datetime' => self::field('String', $label, ['key' => $key, 'kind' => 'value'], ['filters' => ['date']]),
                'richtext', 'textarea' => self::field('String', $label, ['key' => $key, 'kind' => 'value'], ['filters' => ['limit']]),
                default => self::field('String', $label, ['key' => $key, 'kind' => 'value']),
            };
        }

        return $config;
    }

    /**
     * @param  array<int, array<string, mixed>>  $subfields
     * @return array<string, mixed>
     */
    private static function repeaterItemType(string $itemType, string $label, array $subfields): array
    {
        $fields = [];

        foreach ($subfields as $subfield) {
            $key = (string) ($subfield['key'] ?? '');

            if ($key === '') {
                continue;
            }

            $fields[self::fieldName($key)] = self::field('String', (string) ($subfield['label'] ?? $key), ['key' => $key, 'kind' => 'item']);
        }

        return ['fields' => $fields, 'metadata' => ['type' => true, 'label' => "{$label} item"]];
    }

    /**
     * @return array<string, mixed>
     */
    private static function singleQuery(string $type, string $slug, string $name): array
    {
        return [
            'type' => $type,
            'args' => [
                'slug' => ['type' => 'String'],
                'from_url' => ['type' => 'Boolean'],
            ],
            'metadata' => [
                'label' => "Flexa {$name}",
                'group' => self::GROUP,
                'fields' => [
                    'slug' => [
                        'label' => 'Entry slug',
                        'description' => 'The slug (or ID) of the entry in Flexa.',
                    ],
                    'from_url' => [
                        'label' => 'Entry from URL',
                        'type' => 'checkbox',
                        'text' => 'Read the slug from the ?entry= URL parameter (for detail pages)',
                    ],
                ],
            ],
            'extensions' => [
                'call' => ['func' => Resolver::class.'::entry', 'args' => ['entity' => $slug]],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function listQuery(string $type, string $slug, string $name): array
    {
        return [
            'type' => ['listOf' => $type],
            'args' => [
                'category' => ['type' => 'Int'],
                'search' => ['type' => 'String'],
                'offset' => ['type' => 'Int'],
                'limit' => ['type' => 'Int'],
            ],
            'metadata' => [
                'label' => "Flexa {$name} (list)",
                'group' => self::GROUP,
                'fields' => [
                    'category' => [
                        'label' => 'Category ID',
                        'description' => 'Only entries in this Flexa category. Leave empty for all.',
                        'type' => 'number',
                    ],
                    'search' => [
                        'label' => 'Search',
                        'description' => 'Only entries matching this text.',
                    ],
                    '_offset_limit' => [
                        'description' => 'Set the starting point and limit the number of entries.',
                        'type' => 'grid',
                        'width' => '1-2',
                        'fields' => [
                            'offset' => [
                                'label' => 'Start',
                                'type' => 'number',
                                'default' => 0,
                                'modifier' => 1,
                                'attrs' => ['min' => 1, 'required' => true],
                            ],
                            'limit' => [
                                'label' => 'Quantity',
                                'type' => 'limit',
                                'default' => 10,
                                'attrs' => ['min' => 1],
                            ],
                        ],
                    ],
                ],
            ],
            'extensions' => [
                'call' => ['func' => Resolver::class.'::entries', 'args' => ['entity' => $slug]],
            ],
        ];
    }

    /**
     * @param  string|array<string, string>  $type
     * @param  array<string, string>  $resolve  Static resolver arguments (must be JSON serializable).
     * @param  array<string, mixed>  $metadata
     * @return array<string, mixed>
     */
    private static function field(string|array $type, string $label, array $resolve, array $metadata = []): array
    {
        return [
            'type' => $type,
            'metadata' => ['label' => $label, ...$metadata],
            'extensions' => [
                'call' => ['func' => Resolver::class.'::field', 'args' => $resolve],
            ],
        ];
    }

    /**
     * GraphQL field names: letters, digits and underscores, not starting with a digit.
     */
    private static function fieldName(string $key): string
    {
        $name = preg_replace('/[^A-Za-z0-9_]/', '_', $key) ?? $key;

        return preg_match('/^[0-9]/', $name) ? 'f_'.$name : $name;
    }

    private static function pascal(string $key): string
    {
        return implode('', array_map('ucfirst', preg_split('/[^A-Za-z0-9]+/', $key, -1, PREG_SPLIT_NO_EMPTY) ?: [$key]));
    }
}
