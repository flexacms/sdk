<?php

namespace FlexaCms\WordPress;

/**
 * [flexa_entries entity="posts" per_page="6" image="cover" excerpt="excerpt" link="/blog/?entry={slug}"]
 * [flexa_entry entity="posts" slug_param="entry"]
 * [flexa_entry entity="pages" slug="about" field="headline"]  (tag="span" to use it inside a sentence)
 */
final class Shortcodes
{
    public static function register(): void
    {
        add_shortcode('flexa_entries', [self::class, 'entries']);
        add_shortcode('flexa_entry', [self::class, 'entry']);
    }

    /**
     * @param  array<string, string>|string  $attributes
     */
    public static function entries($attributes): string
    {
        return Renderer::entries(shortcode_atts([
            'entity' => '',
            'status' => 'published',
            'category' => 0,
            'search' => '',
            'per_page' => 6,
            'layout' => 'grid',
            'columns' => 3,
            'image' => '',
            'excerpt' => '',
            'link' => '',
            'class' => '',
        ], (array) $attributes, 'flexa_entries'));
    }

    /**
     * @param  array<string, string>|string  $attributes
     */
    public static function entry($attributes): string
    {
        return Renderer::entry(shortcode_atts([
            'entity' => '',
            'slug' => '',
            'slug_param' => '',
            'field' => '',
            'tag' => 'div',
            'image' => '',
            'class' => '',
        ], (array) $attributes, 'flexa_entry'));
    }
}
