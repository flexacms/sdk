/**
 * Shapes returned by the Flexa content API (`/api/v1`). Keys are kept exactly
 * as the API sends them (snake_case), so responses can be passed around as-is.
 */

export type EntryStatus = 'draft' | 'published' | 'archived';

/** Values of a translatable field: one entry per configured locale. */
export type Localized<T = string> = { [locale: string]: T | null | undefined };

/** Field values of an entry, keyed by field key. Narrowed by generated types. */
export type EntryValues = Record<string, unknown>;

/**
 * Maps entity slugs to the values of their entries. Generate it for your
 * workspace with `npx @flexacms/cli generate` to get typed `client.entity()`.
 */
export type ContentSchema = Record<string, EntryValues>;

export interface Category {
    id: number;
    name: string;
    slug: string;
}

export interface RelatedEntry {
    id: number;
    title: string | null;
    slug: string | null;
    status: EntryStatus;
}

export interface Entry<TValues extends object = EntryValues> {
    id: number;
    entity_id: number;
    entity_slug: string;
    title: string | null;
    slug: string | null;
    status: EntryStatus;
    order: number | null;
    categories: Category[];
    values: TValues;
    /** Present when the request includes `relations`. */
    relations?: Record<string, RelatedEntry[]>;
    created_at: string | null;
    updated_at: string | null;
    deleted_at: string | null;
}

export interface EntitySummary {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    icon: string | null;
    color: string | null;
    links: { records: string; schema: string };
}

export type FieldType =
    | 'text'
    | 'textarea'
    | 'number'
    | 'email'
    | 'url'
    | 'phone'
    | 'date'
    | 'datetime'
    | 'boolean'
    | 'select'
    | 'multiselect'
    | 'color'
    | 'file'
    | 'image'
    | 'richtext'
    | 'json'
    | 'tags'
    | 'repeater'
    | 'address'
    | 'relationship'
    | (string & {});

export interface RepeaterSubfield {
    key: string;
    label: string;
    type: FieldType;
    [setting: string]: unknown;
}

export interface FieldSchema {
    id: number;
    key: string;
    label: string;
    type: FieldType;
    description: string | null;
    placeholder: string | null;
    default_value: string | null;
    is_required: boolean;
    is_active: boolean;
    is_searchable: boolean;
    is_shown_in_list: boolean;
    width: string;
    order: number;
    group: string | null;
    options: Record<string, string> | null;
    validation_rules: string[];
    settings: Record<string, unknown>;
    repeater_subfields: RepeaterSubfield[] | null;
    category_ids: number[];
    show_when: unknown;
    translatable: boolean;
    multiple: boolean;
    value_format: string;
    relationship?: { entity: string | null; multiple: boolean };
    address_parts?: string[];
}

export interface FieldGroupSchema {
    id: number;
    key: string;
    label: string;
    description: string | null;
    order: number;
    is_collapsible: boolean;
    is_collapsed: boolean;
}

export interface EntitySchema {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    icon: string | null;
    color: string | null;
    is_active: boolean;
    show_in_menu: boolean;
    menu_order: number;
    settings: Record<string, unknown>;
    fields_count: number;
    records_count: number;
    fields: FieldSchema[];
    groups: FieldGroupSchema[];
    categories: (Category & {
        parent_id: number | null;
        description: string | null;
    })[];
    record_statuses: EntryStatus[];
    locales: string[];
}

export interface PaginationMeta {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
    path: string;
}

export interface Paginated<T> {
    data: T[];
    meta: PaginationMeta;
    links: {
        first: string | null;
        last: string | null;
        prev: string | null;
        next: string | null;
    };
}

export interface ListParams {
    status?: EntryStatus;
    search?: string;
    /** Category id. */
    category?: number;
    page?: number;
    /** Capped by the server (100 by default). */
    perPage?: number;
    /** Include soft-deleted entries (`with`) or only them (`only`). Needs the Write ability. */
    trashed?: 'with' | 'only';
    /** Expand relationship fields into `entry.relations`. */
    includeRelations?: boolean;
}

export interface GetParams {
    includeRelations?: boolean;
}

export interface EntryInput<TValues extends object = EntryValues> {
    title?: string | null;
    slug?: string | null;
    status?: EntryStatus;
    order?: number;
    /** Category ids or slugs. */
    categories?: (number | string)[];
    values?: Partial<TValues>;
}

/**
 * Per-request options. `init` is merged into the `fetch` call, which is how
 * framework adapters pass cache settings (e.g. Next.js `next: { tags }`).
 */
export interface RequestOptions {
    signal?: AbortSignal;
    init?: RequestInit & Record<string, unknown>;
}
