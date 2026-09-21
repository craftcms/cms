import type {InjectionKey} from 'vue';
import type {ComboboxOptionData} from '@craftcms/ui/components/combobox/combobox';

/** One `sourceDataCols` entry — a heading in the imported file. */
export interface SourceDataCol {
  label: string;
  value: string;
  /** `hint` is the incoming file's first-row value for this column, if any. */
  data?: ComboboxOptionData | null;
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
  canBeSet?: boolean;
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
 * A handle-keyed tree shaped like `MappingValues['map']`, holding either the guessed
 * source column for each leaf (what the server sends) or a flag marking the leaves a
 * guess was written into (what the screen keeps).
 */
export type SuggestedMap = Record<string, unknown>;

/**
 * What a row needs from the screen around it. Provided by both the page and the
 * nested panel, so the table renders identically in either.
 */
export interface MappingContext {
  values: MappingValues;
  /**
   * Which `values.map` leaves hold a guessed value rather than an explicit choice,
   * keyed the same way. Kept apart from `MappingValues` so it's never posted with the
   * form.
   */
  suggestedMap: SuggestedMap;
  sourceDataCols: SourceDataCol[];
  editable: boolean;
  /** Opens a container column's own mapping in a nested panel. */
  openNested(col: MappingCol, opener: HTMLElement | null): void;
}

export const MappingContextKey: InjectionKey<MappingContext> = Symbol(
  'importMappingContext'
);
