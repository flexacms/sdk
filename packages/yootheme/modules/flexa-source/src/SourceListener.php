<?php

namespace FlexaCms\YOOtheme;

// Loaded by WordPress or Joomla only.
if ( ! defined( 'ABSPATH' ) ) {
    defined('_JEXEC') or exit;
}

use Throwable;

/**
 * Registers Flexa content models as YOOtheme Pro dynamic content sources.
 *
 * YOOtheme rebuilds its schema cache whenever the builder opens, and the site
 * renders from that cache. So the schemas of the last successful load are kept
 * next to it: if Flexa cannot be reached at that moment, the Flexa sources stay
 * in the schema instead of vanishing from every page that uses them.
 */
final class SourceListener
{
    private const SCHEMAS_FILE = 'flexa-schemas.json';

    /**
     * Where the last loaded schemas are kept. Defaults to YOOtheme's cache directory
     * (the image cache is not configured yet when sources load in the builder).
     */
    public static ?string $cacheDirectory = null;

    /**
     * @param  \YOOtheme\Builder\Source  $source
     */
    public static function initSource($source): void
    {
        $client = Connection::client();

        if ($client === null) {
            return;
        }

        $schemas = self::loadSchemas($client) ?? self::lastLoadedSchemas();

        if ($schemas === null) {
            return;
        }

        $types = TypeBuilder::build($schemas);

        foreach ($types['objectTypes'] as $name => $config) {
            $source->objectType($name, $config);
        }

        foreach ($types['queryTypes'] as $config) {
            $source->queryType($config);
        }
    }

    /**
     * @return array<int, array<string, mixed>>|null Null when Flexa cannot be reached.
     */
    private static function loadSchemas(object $client): ?array
    {
        try {
            $schemas = array_map(
                static fn (array $entity): array => $client->entity((string) $entity['slug'])->schema(),
                $client->entities(),
            );
        } catch (Throwable) {
            return null;
        }

        $file = self::schemasFile();

        if ($file !== null) {
            @file_put_contents($file, (string) json_encode($schemas)); // phpcs:ignore WordPress.WP.AlternativeFunctions, WordPress.PHP.NoSilencedErrors -- a cache file in YOOtheme's cache directory.
        }

        return $schemas;
    }

    /**
     * @return array<int, array<string, mixed>>|null
     */
    private static function lastLoadedSchemas(): ?array
    {
        $file = self::schemasFile();

        if ($file === null || ! is_file($file)) {
            return null;
        }

        $schemas = json_decode((string) file_get_contents($file), true); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- a local cache file.

        return is_array($schemas) ? $schemas : null;
    }

    private static function schemasFile(): ?string
    {
        $directory = self::$cacheDirectory;

        if ($directory === null && function_exists('YOOtheme\app')) {
            try {
                $config = \YOOtheme\app(\YOOtheme\Config::class);
                $directory = $config->get('image.cacheDir') ?? $config->get('app.cacheDir');
            } catch (Throwable) {
                $directory = null;
            }
        }

        return is_string($directory) && $directory !== '' && is_dir($directory)
            ? rtrim($directory, '/\\').'/'.self::SCHEMAS_FILE
            : null;
    }
}
