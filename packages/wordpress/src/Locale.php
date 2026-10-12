<?php

namespace FlexaCms\WordPress;

/**
 * The language to read translatable Flexa fields in: the current WPML or
 * Polylang language, otherwise the WordPress locale.
 */
final class Locale
{
    /**
     * Preferred locale first, then fallbacks (e.g. ["pt_BR", "pt", "en"]).
     *
     * @return array<int, string>
     */
    public static function candidates(): array
    {
        $current = self::current();
        $default = str_replace('-', '_', get_locale());
        $candidates = [$current, self::language($current), self::language($default), 'en'];

        /**
         * Filters the locales used to resolve translatable Flexa fields, in order of preference.
         *
         * @param  array<int, string>  $candidates
         */
        $candidates = (array) apply_filters('flexa_locales', array_values(array_unique(array_filter($candidates))));

        return array_values(array_map('strval', $candidates));
    }

    private static function current(): string
    {
        if (function_exists('pll_current_language')) {
            $language = pll_current_language('locale');

            if (is_string($language) && $language !== '') {
                return str_replace('-', '_', $language);
            }
        }

        if (defined('ICL_LANGUAGE_CODE') && is_string(ICL_LANGUAGE_CODE) && ICL_LANGUAGE_CODE !== '') {
            return ICL_LANGUAGE_CODE;
        }

        return str_replace('-', '_', determine_locale());
    }

    private static function language(string $locale): string
    {
        return strtolower((string) strtok($locale, '_'));
    }
}
