<?php

/**
 * @var array<string, mixed> $attributes
 */

use FlexaCms\WordPress\Renderer;

if (! defined('ABSPATH')) {
    exit;
}

$flexa_html = Renderer::entries([
    'entity' => (string) ($attributes['entity'] ?? ''),
    'per_page' => (int) ($attributes['perPage'] ?? 6),
    'layout' => (string) ($attributes['layout'] ?? 'grid'),
    'columns' => (int) ($attributes['columns'] ?? 3),
    'image' => (string) ($attributes['image'] ?? ''),
    'excerpt' => (string) ($attributes['excerpt'] ?? ''),
    'link' => (string) ($attributes['link'] ?? ''),
    'category' => (int) ($attributes['category'] ?? 0),
]);

if ($flexa_html !== '') {
    printf('<div %s>%s</div>', get_block_wrapper_attributes(), $flexa_html); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped by the renderer and WordPress.
}
