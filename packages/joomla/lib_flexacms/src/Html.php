<?php

namespace FlexaCms\Joomla;

use FlexaCms\Joomla\Core\Entry;
use Joomla\CMS\Filter\InputFilter;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;

\defined('_JEXEC') or exit;

/**
 * Turns Flexa entry fields into safe HTML for layouts.
 */
final class Html
{
    /**
     * One field as HTML: images become <img>, files links, rich text is
     * filtered like article text, lists are joined and booleans are Yes/No.
     */
    public static function field(Entry $entry, string $key): string
    {
        $url = $entry->url($key);

        if ($url !== null) {
            return implode('', array_map(static fn (string $link): string => self::media($link, (string) $entry->title), (array) $url));
        }

        $value = $entry->value($key);

        return match (true) {
            is_bool($value) => htmlspecialchars(Text::_($value ? 'JYES' : 'JNO'), ENT_QUOTES, 'UTF-8'),
            is_int($value), is_float($value) => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'),
            is_string($value) => self::richText($value),
            is_array($value) && array_is_list($value) && array_filter($value, 'is_scalar') === $value => htmlspecialchars(implode(', ', $value), ENT_QUOTES, 'UTF-8'),
            default => '',
        };
    }

    /**
     * A plain field value as text (for titles, alt attributes and the like).
     */
    public static function text(Entry $entry, string $key): string
    {
        $value = $entry->value($key);

        return is_scalar($value) ? htmlspecialchars(strip_tags((string) $value), ENT_QUOTES, 'UTF-8') : '';
    }

    /**
     * The first URL of an image or file field, or null.
     */
    public static function firstUrl(Entry $entry, string $key): ?string
    {
        if ($key === '') {
            return null;
        }

        $url = $entry->url($key);

        return is_array($url) ? ($url[0] ?? null) : $url;
    }

    /**
     * A file URL as an <img> for images, a link otherwise.
     */
    public static function media(string $url, string $label = ''): string
    {
        $extension = strtolower(pathinfo((string) parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION));
        $safeUrl = htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
        $safeLabel = htmlspecialchars($label, ENT_QUOTES, 'UTF-8');

        if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'avif', 'svg'], true)) {
            return HTMLHelper::_('image', $url, $label, ['loading' => 'lazy', 'decoding' => 'async']);
        }

        return '<a href="'.$safeUrl.'">'.($safeLabel !== '' ? $safeLabel : htmlspecialchars(basename($url), ENT_QUOTES, 'UTF-8')).'</a>';
    }

    /**
     * Rich text from Flexa, cleaned with Joomla's HTML filter (no scripts or event handlers).
     */
    private static function richText(string $value): string
    {
        if ($value === strip_tags($value)) {
            return nl2br(htmlspecialchars($value, ENT_QUOTES, 'UTF-8'));
        }

        return InputFilter::getInstance([], [], InputFilter::ONLY_BLOCK_DEFINED_TAGS, InputFilter::ONLY_BLOCK_DEFINED_ATTRIBUTES)->clean($value, 'html');
    }
}
