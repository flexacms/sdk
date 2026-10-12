<?php

namespace FlexaCms\Laravel;

use ArrayAccess;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Carbon;
use JsonSerializable;
use LogicException;

/**
 * An entry returned by the Flexa content API.
 *
 * Field values are read with `$entry->value('headline')`, which resolves
 * translatable values to the app locale, or as array keys (`$entry['headline']`).
 *
 * @implements Arrayable<string, mixed>
 * @implements ArrayAccess<string, mixed>
 */
class Entry implements Arrayable, ArrayAccess, JsonSerializable
{
    /**
     * @param  array<int, array{id: int, name: string, slug: string}>  $categories
     * @param  array<string, mixed>  $values
     * @param  array<string, mixed>  $media  Absolute file URLs of image and file fields.
     * @param  array<string, array<int, array<string, mixed>>>|null  $relations
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
        public readonly ?Carbon $createdAt,
        public readonly ?Carbon $updatedAt,
        protected readonly array $attributes,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: (int) $data['id'],
            entity: (string) ($data['entity_slug'] ?? ''),
            title: $data['title'] ?? null,
            slug: $data['slug'] ?? null,
            status: (string) ($data['status'] ?? 'draft'),
            categories: $data['categories'] ?? [],
            values: $data['values'] ?? $data['data'] ?? [],
            media: $data['media'] ?? [],
            relations: $data['relations'] ?? null,
            createdAt: isset($data['created_at']) ? Carbon::parse($data['created_at']) : null,
            updatedAt: isset($data['updated_at']) ? Carbon::parse($data['updated_at']) : null,
            attributes: $data,
        );
    }

    /**
     * A field value. Translatable values resolve to `$locale` (the app locale by
     * default), then the fallback locale, then any non-empty translation.
     */
    public function value(string $key, ?string $locale = null, mixed $default = null): mixed
    {
        $value = $this->values[$key] ?? null;

        if ($value === null) {
            return $default;
        }

        if (! $this->isTranslation($value)) {
            return $value;
        }

        return $this->translate($value, $locale) ?? $default;
    }

    /**
     * Pick a translation: the locale, then the fallback locale, then any non-empty one.
     *
     * @param  array<string, mixed>  $translations
     */
    protected function translate(array $translations, ?string $locale): mixed
    {
        foreach (array_unique([$locale ?? app()->getLocale(), app()->getFallbackLocale()]) as $candidate) {
            if (filled($translations[$candidate] ?? null)) {
                return $translations[$candidate];
            }
        }

        foreach ($translations as $translation) {
            if (filled($translation)) {
                return $translation;
            }
        }

        return null;
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

        return $this->isTranslation($url) ? $this->translate($url, $locale) : $url;
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
        throw new LogicException('Flexa entries are read-only. Use Flexa::entity()->update() to change them.');
    }

    public function offsetUnset(mixed $offset): void
    {
        throw new LogicException('Flexa entries are read-only. Use Flexa::entity()->update() to change them.');
    }

    /**
     * Translatable values are objects keyed by locale codes (en, es, pt_BR...).
     */
    protected function isTranslation(mixed $value): bool
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
}
