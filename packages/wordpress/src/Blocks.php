<?php

namespace FlexaCms\WordPress;

/**
 * Dynamic blocks rendered on the server, so content stays current without re-saving posts.
 */
final class Blocks
{
    public static function register(): void
    {
        add_action('init', static function (): void {
            register_block_type(FLEXA_DIR.'blocks/entries');
            register_block_type(FLEXA_DIR.'blocks/entry');
        });

        add_filter('block_categories_all', static function (array $categories): array {
            return [...$categories, ['slug' => 'flexa', 'title' => __('Flexa', 'flexa')]];
        });
    }
}
