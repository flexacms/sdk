<?php

namespace FlexaCms\WordPress;

use FlexaCms\WordPress\Core\Entry;
use FlexaCms\WordPress\Core\Exceptions\FlexaException;

/**
 * Renders Flexa content for shortcodes and blocks.
 *
 * Markup comes from templates/*.php; a theme can override any of them by
 * placing a copy in `{theme}/flexa/`.
 */
final class Renderer
{
    /**
     * @param  array<string, mixed>  $args  entity, status, category, search, per_page, page,
     *                                      layout, columns, image, excerpt, link, class
     */
    public static function entries(array $args): string
    {
        $args = wp_parse_args($args, [
            'entity' => '',
            'status' => 'published',
            'category' => 0,
            'search' => '',
            'per_page' => 6,
            'page' => 1,
            'layout' => 'grid',
            'columns' => 3,
            'image' => '',
            'excerpt' => '',
            'link' => '',
            'class' => '',
        ]);

        if ($args['entity'] === '') {
            return self::problem(__('Choose a content model (entity).', 'flexa'));
        }

        return self::guard(static function () use ($args): string {
            $page = flexa_entries((string) $args['entity'], $args);

            if (count($page) === 0) {
                return '';
            }

            wp_enqueue_style('flexa');

            return self::template('entries', ['page' => $page, 'entries' => $page->items, 'args' => $args]);
        });
    }

    /**
     * @param  array<string, mixed>  $args  entity, slug, slug_param, field, tag, image, class
     */
    public static function entry(array $args): string
    {
        $args = wp_parse_args($args, ['entity' => '', 'slug' => '', 'slug_param' => '', 'field' => '', 'tag' => 'div', 'image' => '', 'class' => '']);
        $slug = (string) $args['slug'];

        if ($args['slug_param'] !== '' && isset($_GET[(string) $args['slug_param']])) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            $slug = sanitize_title(wp_unslash((string) $_GET[(string) $args['slug_param']])); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        }

        if ($args['entity'] === '' || $slug === '') {
            return self::problem(__('Choose a content model (entity) and an entry slug.', 'flexa'));
        }

        return self::guard(static function () use ($args, $slug): string {
            $entry = flexa_entry((string) $args['entity'], $slug);

            if ($entry === null) {
                return self::problem(sprintf(
                    /* translators: 1: entry slug, 2: content model */
                    __('Entry "%1$s" was not found in "%2$s".', 'flexa'),
                    $slug,
                    (string) $args['entity'],
                ));
            }

            if ($args['field'] !== '') {
                $html = self::field($entry, (string) $args['field']);
                $tag = $args['tag'] === 'span' ? 'span' : 'div';

                return $html === '' ? '' : sprintf(
                    '<%1$s class="flexa-field flexa-field--%2$s %3$s">%4$s</%1$s>',
                    $tag,
                    esc_attr(sanitize_html_class((string) $args['field'])),
                    esc_attr((string) $args['class']),
                    $html,
                );
            }

            wp_enqueue_style('flexa');

            return self::template('entry', ['entry' => $entry, 'args' => $args]);
        });
    }

    /**
     * One field as HTML: images become <img>, files links, rich text is kept
     * (filtered by wp_kses_post), lists are joined and booleans are Yes/No.
     */
    public static function field(Entry $entry, string $key): string
    {
        $url = $entry->url($key);

        if ($url !== null) {
            return implode('', array_map(static fn (string $link): string => self::media($link, (string) $entry->title), (array) $url));
        }

        $value = $entry->value($key);

        return match (true) {
            is_bool($value) => esc_html($value ? __('Yes', 'flexa') : __('No', 'flexa')),
            is_int($value), is_float($value) => esc_html((string) $value),
            is_string($value) => wp_kses_post($value),
            is_array($value) && array_is_list($value) && array_filter($value, 'is_scalar') === $value => esc_html(implode(', ', $value)),
            default => '',
        };
    }

    /**
     * A file URL as an <img> for images, a link otherwise.
     */
    public static function media(string $url, string $label = ''): string
    {
        $extension = strtolower(pathinfo((string) wp_parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION));

        if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'avif', 'svg'], true)) {
            return sprintf('<img src="%s" alt="%s" loading="lazy" decoding="async" />', esc_url($url), esc_attr($label));
        }

        return sprintf('<a href="%s">%s</a>', esc_url($url), esc_html($label !== '' ? $label : basename($url)));
    }

    /**
     * The link of an entry from a pattern like "/blog/?entry={slug}".
     */
    public static function link(Entry $entry, string $pattern): string
    {
        if ($pattern === '') {
            return '';
        }

        return strtr($pattern, [
            '{slug}' => rawurlencode((string) ($entry->slug ?? $entry->id)),
            '{id}' => (string) $entry->id,
        ]);
    }

    /**
     * Locate and render a template, preferring the theme's copy in `flexa/`.
     *
     * @param  array<string, mixed>  $vars
     */
    public static function template(string $name, array $vars): string
    {
        $file = locate_template(["flexa/{$name}.php"]) ?: FLEXA_DIR."templates/{$name}.php";

        /** Filters the template file used to render Flexa content. */
        $file = (string) apply_filters('flexa_template', $file, $name, $vars);

        ob_start();
        (static function (string $__file, array $__vars): void {
            extract($__vars, EXTR_SKIP); // phpcs:ignore WordPress.PHP.DontExtract.extract_extract
            include $__file;
        })($file, $vars);

        return (string) ob_get_clean();
    }

    /**
     * Run a render callback, turning API failures into an admin-only notice.
     *
     * @param  callable(): string  $render
     */
    private static function guard(callable $render): string
    {
        if (Plugin::client() === null) {
            return self::problem(__('Flexa is not connected. Add the URL and API key in Settings → Flexa.', 'flexa'));
        }

        try {
            return $render();
        } catch (FlexaException $exception) {
            error_log('[Flexa] '.$exception->getMessage()); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log

            return self::problem($exception->getMessage());
        }
    }

    /**
     * Problems are shown to editors only; visitors get nothing.
     */
    private static function problem(string $message): string
    {
        if (! current_user_can('edit_posts')) {
            return '';
        }

        return sprintf('<div class="flexa-notice">%s %s</div>', '<strong>Flexa:</strong>', esc_html($message));
    }
}
