import type { ContentSchema, EntryValues } from '@flexacms/client';

/**
 * Register your generated schema to type the composables:
 *
 *     // types/flexa.d.ts
 *     import type { FlexaSchema } from './flexa';
 *
 *     declare module '@flexacms/nuxt' {
 *         interface FlexaRegister {
 *             schema: FlexaSchema;
 *         }
 *     }
 */
// eslint-disable-next-line @typescript-eslint/no-empty-object-type
export interface FlexaRegister {}

export type RegisteredSchema = FlexaRegister extends {
    schema: infer TSchema extends object;
}
    ? TSchema
    : ContentSchema;

export type EntityName =
    | Extract<keyof RegisteredSchema, string>
    | (string & {});

export type ValuesOf<TEntity extends string> =
    TEntity extends keyof RegisteredSchema
        ? RegisteredSchema[TEntity] extends object
            ? RegisteredSchema[TEntity]
            : EntryValues
        : EntryValues;
