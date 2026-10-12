<?php

use FlexaCms\YOOtheme\Resolver;
use FlexaCms\YOOtheme\SourceListener;
use FlexaCms\YOOtheme\TypeBuilder;

afterEach(function () {
    unset($GLOBALS['flexa_client']);
});

/**
 * A stand-in for YOOtheme\Builder\Source that records what is registered.
 */
function fakeSource(): object
{
    return new class
    {
        public array $objectTypes = [];

        public array $queryTypes = [];

        public function objectType(string $name, array $config): void
        {
            $this->objectTypes[$name] = $config;
        }

        public function queryType(array $config): void
        {
            $this->queryTypes[] = $config;
        }
    };
}

test('every content model becomes a type with a single and a list query', function () {
    $types = TypeBuilder::build(workspaceSchemas());
    $queries = $types['queryTypes'][0]['fields'];

    expect(array_keys($types['objectTypes']))->toContain('FlexaPages', 'FlexaPosts', 'FlexaTestimonials', 'FlexaFaqs', 'FlexaPagesSectionsItem', 'FlexaFile')
        ->and(array_keys($queries))->toContain('flexaPages', 'flexaPagesList', 'flexaPosts', 'flexaPostsList')
        ->and($queries['flexaPagesList']['type'])->toBe(['listOf' => 'FlexaPages'])
        ->and($queries['flexaPages']['metadata']['group'])->toBe('Flexa')
        ->and($queries['flexaPages']['extensions']['call']['args'])->toBe(['entity' => 'pages']);
});

test('fields are mapped by type, with builder filters', function () {
    $pages = TypeBuilder::build(workspaceSchemas())['objectTypes']['FlexaPages']['fields'];
    $posts = TypeBuilder::build(workspaceSchemas())['objectTypes']['FlexaPosts']['fields'];

    expect($pages['hero_image']['extensions']['call']['args'])->toBe(['key' => 'hero_image', 'kind' => 'media'])
        ->and($pages['sections']['type'])->toBe(['listOf' => 'FlexaPagesSectionsItem'])
        ->and($pages['created_at']['metadata']['filters'])->toBe(['date'])
        ->and($pages['subheadline']['metadata']['filters'])->toBe(['limit'])
        ->and($posts['gallery_all']['type'])->toBe(['listOf' => 'FlexaFile'])
        ->and($posts)->not->toHaveKey('cover_all');
});

test('the schema is cacheable: resolvers are static methods and their arguments are JSON', function () {
    $types = TypeBuilder::build(workspaceSchemas());
    $configs = [...array_values($types['objectTypes']), ...$types['queryTypes']];

    foreach ($configs as $config) {
        foreach ($config['fields'] as $field) {
            [$class, $method] = explode('::', $field['extensions']['call']['func']);

            expect(method_exists($class, $method))->toBeTrue()
                ->and(json_encode($field['extensions']['call']['args']))->not->toBeFalse();
        }
    }
});

test('names are valid GraphQL identifiers', function () {
    $types = TypeBuilder::build([[
        'slug' => '2024-news',
        'name' => 'News',
        'fields' => [['key' => 'cta-url', 'label' => 'CTA', 'type' => 'url'], ['key' => '3d_model', 'label' => '3D', 'type' => 'file']],
    ]]);

    expect($types['objectTypes'])->toHaveKey('FlexaEntity2024News')
        ->and(array_keys($types['objectTypes']['FlexaEntity2024News']['fields']))->toContain('cta_url', 'f_3d_model');
});

test('the listener registers nothing when Flexa is not connected or unreachable', function () {
    $source = fakeSource();
    SourceListener::initSource($source);
    expect($source->objectTypes)->toBe([]);

    fakeFlexa([]);
    SourceListener::initSource($source);
    expect($source->objectTypes)->toBe([]);
});

test('the listener registers the workspace content models', function () {
    $schemas = workspaceSchemas();
    fakeFlexa([
        '/entities' => ['data' => array_map(fn ($schema) => ['slug' => $schema['slug']], $schemas)],
        ...array_combine(array_map(fn ($schema) => '/'.$schema['slug'].'/schema', $schemas), array_map(fn ($schema) => ['data' => $schema], $schemas)),
    ]);
    $source = fakeSource();

    SourceListener::initSource($source);

    expect($source->objectTypes)->toHaveKey('FlexaPages')
        ->and($source->queryTypes[0]['fields'])->toHaveKey('flexaPagesList');
});

test('resolvers read entries, translated values, media and repeater items', function () {
    fakeFlexa(['/pages/about' => ['data' => [
        'id' => 7,
        'entity_slug' => 'pages',
        'title' => 'About us',
        'slug' => 'about',
        'status' => 'published',
        'categories' => [['id' => 1, 'name' => 'Company', 'slug' => 'company'], ['id' => 2, 'name' => 'Team', 'slug' => 'team']],
        'values' => [
            'headline' => ['en' => 'Hello', 'es' => 'Hola'],
            'featured' => true,
            'tags' => ['a', 'b'],
            'sections' => [['title' => 'One', 'photo' => 'flex-fields/x/one.webp'], ['title' => 'Two']],
            'gallery' => ['p/1.webp', 'p/2.webp'],
        ],
        'media' => [
            'gallery' => ['https://media.test/1.webp', 'https://media.test/2.webp'],
            'sections' => [['photo' => 'https://media.test/one.webp'], ['photo' => null]],
        ],
        'created_at' => '2026-10-01T10:00:00+00:00',
    ]]]);

    $entry = Resolver::entry(null, ['entity' => 'pages', 'slug' => 'about']);
    $sections = Resolver::field($entry, ['key' => 'sections', 'kind' => 'repeater']);

    expect(Resolver::field($entry, ['key' => 'title', 'kind' => 'attribute']))->toBe('About us')
        ->and(Resolver::field($entry, ['key' => 'id', 'kind' => 'attribute']))->toBe('7')
        ->and(Resolver::field($entry, ['key' => 'categories', 'kind' => 'categories']))->toBe('Company, Team')
        ->and(Resolver::field($entry, ['key' => 'headline', 'kind' => 'value']))->toBe('Hola')
        ->and(Resolver::field($entry, ['key' => 'featured', 'kind' => 'value']))->toBeTrue()
        ->and(Resolver::field($entry, ['key' => 'tags', 'kind' => 'value']))->toBe('a, b')
        ->and(Resolver::field($entry, ['key' => 'gallery', 'kind' => 'media']))->toBe('https://media.test/1.webp')
        ->and(Resolver::field($entry, ['key' => 'gallery', 'kind' => 'media_list']))->toBe([['url' => 'https://media.test/1.webp'], ['url' => 'https://media.test/2.webp']])
        ->and(Resolver::field($sections[0], ['key' => 'photo', 'kind' => 'item']))->toBe('https://media.test/one.webp')
        ->and(Resolver::field($sections[1], ['key' => 'title', 'kind' => 'item']))->toBe('Two')
        ->and(Resolver::field($sections[1], ['key' => 'photo', 'kind' => 'item']))->toBeNull();
});

test('the single query can take the slug from the URL, sanitized', function () {
    fakeFlexa(['/pages/about' => ['data' => ['id' => 7, 'title' => 'About us', 'slug' => 'about']]]);
    $_GET['entry'] = '"about"/><';

    $entry = Resolver::entry(null, ['entity' => 'pages', 'slug' => 'home', 'from_url' => true]);

    expect($entry?->title)->toBe('About us')
        ->and(Resolver::entry(null, ['entity' => 'pages', 'slug' => 'missing']))->toBeNull();
    unset($_GET['entry']);
});

test('the list query pages through results with offset and limit', function () {
    $page = fn (array $ids, int $current, int $last) => ['data' => array_map(fn ($id) => ['id' => $id, 'title' => "Post {$id}"], $ids), 'meta' => ['current_page' => $current, 'last_page' => $last, 'total' => 150, 'per_page' => 100]];
    fakeFlexa(['/posts' => $page(range(1, 100), 1, 2)]);

    $entries = Resolver::entries(null, ['entity' => 'posts', 'offset' => 2, 'limit' => 3, 'category' => 4, 'search' => 'x']);

    expect(array_map(fn ($entry) => $entry->id, $entries))->toBe([3, 4, 5])
        ->and($GLOBALS['flexa_transport']->paths)->toBe(['/posts?category=4&page=1&per_page=100&search=x&status=published']);
});
