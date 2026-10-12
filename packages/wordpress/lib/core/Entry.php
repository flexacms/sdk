<?php

// Generated from flexacms/php by scripts/sync-php-core.php. Do not edit: change packages/php instead.

namespace FlexaCms\WordPress\Core;

use ArrayAccess;
use JsonSerializable;
use LogicException;

/**
 * An entry returned by the Flexa content API.
 *
 * `value('headline')` resolves translatable values to the client's locale,
 * `url('cover')` returns the public URL of a file field.
 *
 * @implements ArrayAccess<string, mixed>
 */
final class Entry implements ArrayAccess, JsonSerializable
{
    /**
     * @param  array<int, array{id: int, name: string, slug: string}>  $categories
     * @param  array<string, mixed>  $values  Stored field values (file fields hold disk paths).
     * @param  array<string, mixed>  $media  Absolute URLs of image and file fields.
     * @param  array<string, array<int, array<string, mixed>>>|null  $relations
     * @param  array<int, string>  $fallbackLocales
     * @param  array<string, mixed>  $attributes  The raw API payload.
     */
    public function __construct(
        public readonly int $id,
        public readonly string $entity,
        public readonly ?string $title,
        public readonly ?string $slug,
        public readonly string $status,
        public readonly array $categories,
        public readonly array $values,
        public readonly array $media,
        public readonly ?array $relations,
        public readonly ?string $createdAt,
        public readonly ?string $updatedAt,
        private readonly ?string $locale,
        private readonly array $fallbackLocales,
        private readonly array $attributes,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     * @param  array<int, string>  $fallbackLocales
     */
    public static function fromArray(array $data, ?string $locale = null, array $fallbackLocales = []): self
    {
        return new self(
            id: (int) ($data['id'] ?? 0),
            entity: (string) ($data['entity_slug'] ?? ''),
            title: $data['title'] ?? null,
            slug: $data['slug'] ?? null,
            status: (string) ($data['status'] ?? 'draft'),
            categories: $data['categories'] ?? [],
            values: $data['values'] ?? $data['data'] ?? [],
            media: is_array($data['media'] ?? null) ? $data['media'] : [],
            relations: $data['relations'] ?? null,
            createdAt: $data['created_at'] ?? null,
            updatedAt: $data['updated_at'] ?? null,
            locale: $locale,
            fallbackLocales: $fallbackLocales,
            attributes: $data,
        );
    }

    /**
     * A field value. Translatable values resolve to `$locale` (the client's
     * locale by default), then the fallback locales, then any translation.
     */
    public function value(string $key, ?string $locale = null, mixed $default = null): mixed
    {
        $value = $this->values[$key] ?? null;

        if ($value === null) {
            return $default;
        }

        return self::isTranslation($value) ? ($this->translate($value, $locale) ?? $default) : $value;
    }

    /**
     * Public URL(s) of an image or file field: a string, or a list for
     * multiple fields. Translatable fields resolve like value().
     *
     * @return string|array<int, string>|null
     */
    public function url(string $key, ?string $locale = null): string|array|null
    {
        $url = $this->media[$key] ?? null;

        return self::isTranslation($url) ? $this->translate($url, $locale) : $url;
    }

    public function isPublished(): bool
    {
        return $this->status === 'published';
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->attributes;
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->attributes;
    }

    public function offsetExists(mixed $offset): bool
    {
        return array_key_exists($offset, $this->values);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->value($offset);
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        throw new LogicException('Flexa entries are read-only.');
    }

    public function offsetUnset(mixed $offset): void
    {
        throw new LogicException('Flexa entries are read-only.');
    }

    /**
     * Translatable values are objects keyed by locale codes (en, es, pt_BR...).
     */
    public static function isTranslation(mixed $value): bool
    {
        if (! is_array($value) || $value === [] || array_is_list($value)) {
            return false;
        }

        foreach (array_keys($value) as $key) {
            if (! preg_match('/^[a-z]{2,3}([_-][A-Za-z]{2,4})?$/', (string) $key)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  array<string, mixed>  $translations
     */
    private function translate(array $translations, ?string $locale): mixed
    {
        $candidates = array_unique(array_filter([$locale ?? $this->locale, ...$this->fallbackLocales]));

        foreach ($candidates as $candidate) {
            if (self::filled($translations[$candidate] ?? null)) {
                return $translations[$candidate];
            }
        }

        foreach ($translations as $translation) {
            if (self::filled($translation)) {
                return $translation;
            }
        }

        return null;
    }

    private static function filled(mixed $value): bool
    {
        return $value !== null && $value !== '' && $value !== [];
    }
}
