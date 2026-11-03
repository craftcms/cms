/**
 * Provides the {@link MappingContextKey} a step's mapping table reads and writes
 * through. Container columns expand in place: their own columns are fetched the first
 * time they're shown, and the server's guesses for them are written into the same
 * trees as everything else.
 */
import {provide, reactive, useId, type Ref} from 'vue';
import {t} from '@craftcms/ui';
import type {TextExpanderTriggers} from '@craftcms/ui/components/text-expander/text-expander';
import {fetchNestedMapping} from './nested-mapping';
import {applySuggestions} from './paths';
import {
  MappingContextKey,
  type ImportStep,
  type MappingColumn,
  type MappingContext,
  type MappingValues,
  type NestedColumns,
  type SourceColumn,
  type SuggestedMap,
} from './types';

export interface UseMappingEditorOptions {
  /** Edited in place. */
  values: MappingValues;
  suggestedMap: SuggestedMap;
  sourceDataCols: SourceColumn[];
  templateTextExpanderTriggers: TextExpanderTriggers;
  editable: boolean;
  /** The draft step being mapped, posted when a container's columns are asked for. */
  step: () => ImportStep;
  /** The templates' errors, keyed by the column's path joined with dots. */
  templateErrors: Ref<Record<string, string[]>>;
}

export function useMappingEditor(
  options: UseMappingEditorOptions
): MappingContext {
  const values = reactive<MappingValues>(options.values);
  const suggestedMap = reactive<SuggestedMap>(options.suggestedMap);
  const nested = reactive<Record<string, NestedColumns>>({});
  const expanded = reactive(new Set<string>());

  async function toggleNested(col: MappingColumn): Promise<void> {
    const key = col.prefixedHandle;

    if (expanded.has(key)) {
      expanded.delete(key);

      return;
    }

    if (nested[key]?.groups.length) {
      expanded.add(key);

      return;
    }

    nested[key] = {loading: true, error: null, groups: []};

    try {
      const data = await fetchNestedMapping(options.step(), values, col);
      applySuggestions(values.map, data.suggestions, suggestedMap);
      nested[key] = {loading: false, error: null, groups: data.groups};
      expanded.add(key);
    } catch (error) {
      nested[key] = {
        loading: false,
        error:
          error instanceof Error && error.message
            ? error.message
            : t('Couldn’t load this field’s columns.'),
        groups: [],
      };
    }
  }

  const context: MappingContext = {
    values,
    suggestedMap,
    sourceDataCols: options.sourceDataCols,
    templateTextExpanderTriggers: options.templateTextExpanderTriggers,
    templateErrors: options.templateErrors,
    panelId: useId(),
    editable: options.editable,
    nested,
    expanded,
    toggleNested,
  };

  provide(MappingContextKey, context);

  return context;
}
