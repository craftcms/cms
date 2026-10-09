/**
 * The shell shared by every mapping slideout: `StepMapping.vue` and `NestedMapping.vue`
 * both edit a copy of the mapping trees and provide the same {@link MappingContextKey}
 * for `MappingTable`/`MappingRow` to read and write through, differing only in what
 * they render around that context.
 */
import {provide, reactive, watch} from 'vue';
import {t} from '@craftcms/ui';
import {useForm} from '@inertiajs/vue3';
import {useAppLayout} from '@/common/composables/useAppLayout';
import {useSlideout} from '@/common/slideouts';
import {openNestedMapping} from './nested-mapping';
import {dirtyState} from './paths';
import {
  type MappingColumn,
  MappingContextKey,
  type MappingValues,
  type SourceColumn,
  type ImportStep,
  type SuggestedMap,
} from './types';

export interface UseMappingPanelOptions {
  title: string;
  values: MappingValues;
  suggestedMap: SuggestedMap;
  sourceDataCols: SourceColumn[];
  editable: boolean;
  /** The draft step being mapped, so a nested panel opened from here can post it. */
  step: ImportStep;
  apply(values: MappingValues): void;
}

export function useMappingPanel(options: UseMappingPanelOptions): {
  values: MappingValues;
  suggestedMap: SuggestedMap;
} {
  const slideout = useSlideout();
  const values = reactive<MappingValues>(options.values);
  const suggestedMap = reactive<SuggestedMap>(options.suggestedMap);

  /**
   * Backs the shell's Apply button and gives it an accurate dirty check for the
   * unsaved-changes prompt. Inertia diffs against the value it was created with, so
   * a serialization of the trees stands in for them — `dirtyState()`'s, so a cleared
   * control or a reordered key doesn't read as an edit.
   */
  const form = useForm({state: dirtyState(values)});

  watch(
    values,
    () => {
      form.state = dirtyState(values);
    },
    {deep: true}
  );

  useAppLayout(() => ({
    title: options.title,
    submitButtonLabel: t('Apply'),
    form,
    defaultFormActions: [],
    onSave: apply,
  }));

  provide(MappingContextKey, {
    values,
    suggestedMap,
    sourceDataCols: options.sourceDataCols,
    editable: options.editable,
    openNested(col: MappingColumn, opener: HTMLElement | null): void {
      void openNestedMapping({
        col,
        step: options.step,
        values,
        editable: options.editable,
        opener,
        apply: (applied) => Object.assign(values, applied),
      });
    },
  });

  function apply(): void {
    options.apply(JSON.parse(JSON.stringify(values)) as MappingValues);

    // Before close(): closing drops the panel from the store, and its handler with it.
    slideout?.saved();
    slideout?.close({force: true});
  }

  return {values, suggestedMap};
}
