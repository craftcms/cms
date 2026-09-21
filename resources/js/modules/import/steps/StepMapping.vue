<script setup lang="ts">
  /**
   * One import step's field mapping, as a slideout panel.
   *
   * Opened from the step's own panel — see `step-mapping.ts`. The panel edits its own
   * copy of the step's four mapping trees and hands them back on Apply, so cancelling
   * leaves the step behind untouched.
   */
  import {provide, reactive, watch} from 'vue';
  import {t} from '@craftcms/ui';
  import {useForm} from '@inertiajs/vue3';
  import {useAppLayout} from '@/common/composables/useAppLayout';
  import {useSlideout} from '@/common/slideouts';
  import MappingTable from '@/modules/import/mapping/MappingTable.vue';
  import {openNestedMapping} from '@/modules/import/mapping/nested-mapping';
  import {
    type MappingCol,
    MappingContextKey,
    type MappingValues,
    type SuggestedMap,
  } from '@/modules/import/mapping/types';
  import {takeStepMappingContext} from './step-mapping';

  const props = defineProps<{
    contextId: string;
    title: string;
  }>();

  const context = takeStepMappingContext(props.contextId);
  const slideout = useSlideout();
  const values = reactive<MappingValues>(context.values);
  const suggestedMap = reactive<SuggestedMap>(context.suggestedMap);

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
    title: props.title,
    submitButtonLabel: t('Apply'),
    form,
    onSave: apply,
  }));

  provide(MappingContextKey, {
    values,
    suggestedMap,
    sourceDataCols: context.sourceDataCols,
    editable: context.editable,
    openNested(col: MappingCol, opener: HTMLElement | null): void {
      void openNestedMapping({
        col,
        step: context.step,
        colsUrl: context.urls.nestedColsUrl,
        values,
        editable: context.editable,
        opener,
        apply: (applied) => Object.assign(values, applied),
      });
    },
  });

  function apply(): void {
    context.apply(JSON.parse(JSON.stringify(values)) as MappingValues);

    // Before close(): closing drops the panel from the store, and its handler with it.
    slideout?.saved();
    slideout?.close({force: true});
  }
</script>

<template>
  <div>
    <p>
      {{
        t(
          'If you see any selections marked in blue, they were auto-selected as closest matches from the incoming data. Change them if they’re not right.'
        )
      }}
    </p>

    <MappingTable :cols="context.destinationCols" />
  </div>
</template>
