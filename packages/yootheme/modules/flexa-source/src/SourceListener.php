<?php

namespace FlexaCms\YOOtheme;

// Loaded by WordPress or Joomla only.
if ( ! defined( 'ABSPATH' ) ) {
    defined('_JEXEC') or exit;
}

use Throwable;

/**
 * Registers Flexa content models as YOOtheme Pro dynamic content sources.
 */
final class SourceListener
{
    /**
     * @param  \YOOtheme\Builder\Source  $source
     */
    public static function initSource($source): void
    {
        $client = Connection::client();

        if ($client === null) {
            return;
        }

        try {
            $schemas = array_map(
                static fn (array $entity): array => $client->entity((string) $entity['slug'])->schema(),
                $client->entities(),
            );
        } catch (Throwable) {
            // Not reachable right now: keep the builder working without Flexa sources.
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
}
