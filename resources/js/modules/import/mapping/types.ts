import type {InjectionKey, Ref} from 'vue';
import type {TextExpanderTriggers} from '@craftcms/ui/components/text-expander/text-expander';

/**
 * The `values.map` leaf that marks a column as filled from its template in
 * `values.templates`. Mirrors `ImportHelper::TEMPLATE_RULE`.
 */
export const TEMPLATE_RULE = '__template__';

/** One `sourceDataCols` entry — a heading in the imported file. */
export type SourceColumn = CraftCms.Cms.Import.Data.SourceColumn;

/** A destination column. */
export type MappingColumn = CraftCms.Cms.Import.Data.MappingColumn;

/** One per-field import setting a field type offers, e.g. what to do with a conflicting file. */
export type FieldMappingSetting = CraftCms.Cms.Import.Data.FieldMappingSetting;

/** A destination that maps through several subfield columns under one heading, e.g. lat/long. */
export type CompoundMappingColumn =
  CraftCms.Cms.Import.Data.CompoundMappingColumn;

export type MappingColumnEntry = MappingColumn | CompoundMappingColumn;

/** One field-layout provider's columns, under an expanded container. */
export type MappingColumnGroup = CraftCms.Cms.Import.Data.MappingColumnGroup;

/**
 * One import step, as the edit screen holds it and as it's posted to the server.
 * A draft step is given its uid client-side, so it always has one.
 */
export type ImportStep = Omit<CraftCms.Cms.Import.Data.ImportStep, 'uid'> & {
  uid: string;
};

/** The parallel trees the mapping screen edits, all keyed alike. */
export type MappingValues = CraftCms.Cms.Import.Data.MappingValues;

export type MappingValueTree = keyof MappingValues;

/**
 * A handle-keyed tree shaped like `MappingValues['map']`, holding either the guessed
 * source column for each leaf (what the server sends) or a flag marking the leaves a
 * guess was written into (what the screen keeps).
 */
export type SuggestedMap = Record<string, unknown>;

/** A container column's own columns, once they've been asked for. */
export interface NestedColumns {
  loading: boolean;
  error: string | null;
  groups: MappingColumnGroup[];
}

/** What a row needs from the step's mapping editor around it. */
export interface MappingContext {
  values: MappingValues;
  /**
   * Which `values.map` leaves hold a guessed value rather than an explicit choice,
   * keyed the same way. Kept apart from `MappingValues` so it's never posted with the
   * form.
   */
  suggestedMap: SuggestedMap;
  sourceDataCols: SourceColumn[];
  /** Offers the source columns as variables in a column's template. */
  templateTextExpanderTriggers: TextExpanderTriggers;
  /**
   * The server's errors for the templates, keyed by the column's path joined with
   * dots, from the plan's last save.
   */
  templateErrors: Ref<Record<string, string[]>>;
  /** Marks this editor's invalid template inputs. */
  panelId: string;
  editable: boolean;
  /** A container column's own columns, keyed by its `prefixedHandle`. */
  nested: Record<string, NestedColumns>;
  /** The `prefixedHandle`s of the container columns showing their own columns. */
  expanded: Set<string>;
  /** Shows or hides a container column's own columns, loading them the first time. */
  toggleNested(col: MappingColumn): Promise<void>;
}

export const MappingContextKey: InjectionKey<MappingContext> = Symbol(
  'importMappingContext'
);
