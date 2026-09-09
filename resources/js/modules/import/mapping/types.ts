import type {InjectionKey} from 'vue';

/** One `sourceDataCols` entry — a heading in the imported file. */
export interface SourceDataCol {
    label: string;
    value: string;
}

/**
 * A destination column, as built by `ImportHelper::getPrefixedHandlesForMapping()`
 * and the `getFieldsForMapping()` implementations in `src/FieldLayout`.
 *
 * `prefixedHandleAsArray` is the column's path from the root of the mapping trees,
 * and is how every value below is addressed. The `prefixedHandleFor*` strings are the
 * bracket-named form inputs the Twig screen posted; they survive as stable keys but
 * the Vue screen posts real nested objects instead.
 */
export interface MappingCol {
    handle: string;
    label: string;
    prefixedHandle: string;
    prefixedHandleAsArray: string[];
    prefixedHandleForMap: string;
    prefixedHandleForMatchCriteria: string;
    prefixedHandleForClear: string;
    isContainer: boolean;
    canBeMatchCriteria: boolean;
    canBeCleared: boolean;
    canKeepMissingNestedElements?: boolean;
    isProperty?: boolean;
    /** Containers only — the global field UID, for fetching the nested columns. */
    fieldUid?: string | null;
    prefixedHandleForKeep?: string;
    prefixedHandleForKeepFlag?: string;
}

/** A labelled run of columns rendered under one heading, e.g. lat/long. */
export interface MappingColSet {
    multiple: true;
    heading?: string;
    subfields: MappingCol[];
}

/**
 * `getFieldsForMapping()` returns `[]` for a column it wants skipped, which arrives
 * as an empty array rather than an object.
 */
export type MappingColEntry = MappingCol | MappingColSet | [];

/** One field-layout provider's columns, in the nested panel. */
export interface MappingGroup {
    providerName: string | null;
    destinationCols: MappingColEntry[];
}

/** The four parallel trees the mapping screen edits, all keyed alike. */
export interface MappingValues {
    map: Record<string, unknown>;
    matchCriteria: Record<string, unknown>;
    clearableItems: Record<string, unknown>;
    keepMissingNestedElements: Record<string, unknown>;
}

export type MappingValueTree = keyof MappingValues;

/**
 * What a row needs from the screen around it. Provided by both the page and the
 * nested panel, so the table renders identically in either.
 */
export interface MappingContext {
    values: MappingValues;
    sourceDataCols: SourceDataCol[];
    editable: boolean;
    /** Opens a container column's own mapping in a nested panel. */
    openNested(col: MappingCol, opener: HTMLElement | null): void;
}

export const MappingContextKey: InjectionKey<MappingContext> = Symbol(
    'importMappingContext'
);
