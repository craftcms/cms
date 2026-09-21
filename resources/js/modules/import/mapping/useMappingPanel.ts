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
import {
  type MappingCol,
  MappingContextKey,
  type MappingValues,
  type SourceDataCol,
  type StepPayload,
  type SuggestedMap,
} from './types';

export interface UseMappingPanelOptions {
  title: string;
  values: MappingValues;
  suggestedMap: SuggestedMap;
  sourceDataCols: SourceDataCol[];
  editable: boolean;
  /** The draft step being mapped, so a nested panel opened from here can post it. */
  step: StepPayload;
  /** Endpoint returning a container column's own destination columns. */
  nestedColsUrl: string;
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
   * a serialization of the trees stands in for them.
   */
  const form = useForm({state: JSON.stringify(values)});

  watch(
    values,
    () => {
      form.state = JSON.stringify(values);
    },
    {deep: true}
  );

  useAppLayout(() => ({
    title: options.title,
    submitButtonLabel: t('Apply'),
    form,
    onSave: apply,
  }));

  provide(MappingContextKey, {
    values,
    suggestedMap,
    sourceDataCols: options.sourceDataCols,
    editable: options.editable,
    openNested(col: MappingCol, opener: HTMLElement | null): void {
      void openNestedMapping({
        col,
        step: options.step,
        colsUrl: options.nestedColsUrl,
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
