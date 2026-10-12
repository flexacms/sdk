import type { Localized } from './types';

/**
 * Whether a field value is a translatable value ({ "en": "...", "es": "..." }).
 */
export function isLocalized(
    value: unknown,
    locales?: readonly string[],
): value is Localized<unknown> {
    if (value === null || typeof value !== 'object' || Array.isArray(value)) {
        return false;
    }

    const keys = Object.keys(value);

    if (keys.length === 0) {
        return false;
    }

    return locales
        ? keys.every((key) => locales.includes(key))
        : keys.every((key) => /^[a-z]{2,3}([_-][A-Za-z]{2,4})?$/.test(key));
}

/**
 * Pick one locale of a translatable value, falling back to the given locales
 * (and then to the first non-empty translation). Plain values pass through.
 */
export function localize<T>(
    value: Localized<T> | T | null | undefined,
    locale: string,
    fallbacks: readonly string[] = [],
): T | null {
    if (value === null || value === undefined) {
        return null;
    }

    if (!isLocalized(value)) {
        return value as T;
    }

    const translations = value as Localized<T>;

    for (const candidate of [locale, ...fallbacks]) {
        const translation = translations[candidate];

        if (
            translation !== null &&
            translation !== undefined &&
            translation !== ''
        ) {
            return translation;
        }
    }

    for (const translation of Object.values(translations)) {
        if (
            translation !== null &&
            translation !== undefined &&
            translation !== ''
        ) {
            return translation;
        }
    }

    return null;
}
