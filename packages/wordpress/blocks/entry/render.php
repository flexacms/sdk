<?php

/**
 * @var array<string, mixed> $attributes
 */

use FlexaCms\WordPress\Renderer;

if (! defined('ABSPATH')) {
    exit;
}

$flexa_html = Renderer::entry([
    'entity' => (string) ($attributes['entity'] ?? ''),
    'slug' => (string) ($attributes['slug'] ?? ''),
    'slug_param' => (string) ($attributes['slugParam'] ?? ''),
    'field' => (string) ($attributes['field'] ?? ''),
    'image' => (string) ($attributes['image'] ?? ''),
]);

if ($flexa_html !== '') {
    printf('<div %s>%s</div>', get_block_wrapper_attributes(), $flexa_html); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped by the renderer and WordPress.
}
