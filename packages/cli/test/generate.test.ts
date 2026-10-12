import type { EntitySchema, FieldSchema } from '@flexacms/client';
import { describe, expect, it } from 'vitest';
import { generateTypes } from '../src';

function field(
    overrides: Partial<FieldSchema> & Pick<FieldSchema, 'key' | 'type'>,
): FieldSchema {
    return {
        id: 1,
        label: overrides.key,
        description: null,
        placeholder: null,
        default_value: null,
        is_required: false,
        is_active: true,
        is_searchable: false,
        is_shown_in_list: false,
        width: 'full',
        order: 1,
        group: null,
        options: null,
        validation_rules: [],
        settings: {},
        repeater_subfields: null,
        category_ids: [],
        show_when: null,
        translatable: false,
        multiple: false,
        value_format: '',
        ...overrides,
    };
}

function entity(slug: string, fields: FieldSchema[]): EntitySchema {
    return {
        id: 1,
        name: slug,
        slug,
        description: null,
        icon: null,
        color: null,
        is_active: true,
        show_in_menu: true,
        menu_order: 1,
        settings: {},
        fields_count: fields.length,
        records_count: 0,
        fields,
        groups: [],
        categories: [],
        record_statuses: ['draft', 'published', 'archived'],
        locales: ['en', 'es'],
    };
}

describe('generateTypes', () => {
    const output = generateTypes([
        entity('blog-posts', [
            field({
                key: 'headline',
                type: 'text',
                translatable: true,
                is_required: true,
                label: 'Headline',
                description: 'Shown */ on top',
            }),
            field({ key: 'rating', type: 'number' }),
            field({ key: 'featured', type: 'boolean', is_required: true }),
            field({
                key: 'layout',
                type: 'select',
                options: { grid: 'Grid', list: 'List' },
            }),
            field({
                key: 'colors',
                type: 'multiselect',
                options: { red: 'Red', blue: 'Blue' },
            }),
            field({ key: 'gallery', type: 'image', multiple: true }),
            field({ key: 'author', type: 'relationship' }),
            field({ key: 'data', type: 'json' }),
            field({
                key: 'office',
                type: 'address',
                address_parts: ['street', 'city'],
            }),
            field({
                key: 'sections',
                type: 'repeater',
                repeater_subfields: [
                    {
                        key: 'title',
                        label: 'Title',
                        type: 'text',
                        is_required: true,
                    },
                    {
                        key: 'kind',
                        label: 'Kind',
                        type: 'select',
                        options: { a: 'A' },
                    },
                ],
            }),
            field({ key: 'archived-field', type: 'text', is_active: false }),
            field({ key: 'cta-url', type: 'url' }),
        ]),
        entity('faqs', [
            field({ key: 'answer', type: 'richtext', is_required: true }),
        ]),
    ]);

    it('maps every field type to its value type', () => {
        expect(output).toContain('export interface BlogPostsValues {');
        expect(output).toContain('    headline: Localized<string>;');
        expect(output).toContain('    rating: number | null;');
        expect(output).toContain('    featured: boolean;');
        expect(output).toContain('    layout: ("grid" | "list") | null;');
        expect(output).toContain('    colors: ("red" | "blue")[] | null;');
        expect(output).toContain('    gallery: string[] | null;');
        expect(output).toContain('    author: number | null;');
        expect(output).toContain('    data: unknown | null;');
        expect(output).toContain(
            '    office: { street?: string | null; city?: string | null } | null;',
        );
        expect(output).toContain(
            '    sections: Array<{ title: string; kind?: "a" | null }> | null;',
        );
        expect(output).toContain('    "cta-url": string | null;');
    });

    it('skips inactive fields and keeps comments safe', () => {
        expect(output).not.toContain('archived-field');
        expect(output).toContain('/** Headline — Shown *\\/ on top */');
    });

    it('builds the schema map used by createClient', () => {
        expect(output).toContain(
            "import type { Localized } from '@flexacms/client';",
        );
        expect(output).toContain(
            'export interface FlexaSchema {\n    "blog-posts": BlogPostsValues;\n    faqs: FaqsValues;\n}',
        );
    });
});

describe('generateTypes with a real workspace', () => {
    it('matches the committed fixture used by the typed-client compile check', async () => {
        const { readFile } = await import('node:fs/promises');
        const schemas = JSON.parse(
            await readFile(
                new URL('./fixtures/schemas.json', import.meta.url),
                'utf8',
            ),
        ) as EntitySchema[];

        expect(generateTypes(schemas)).toBe(
            await readFile(
                new URL('./fixtures/flexa.d.ts', import.meta.url),
                'utf8',
            ),
        );
    });
});
