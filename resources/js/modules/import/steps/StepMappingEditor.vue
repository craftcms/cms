<script setup lang="ts">
  /**
   * A step's mapping, edited in place rather than in its own panel. Each change is handed
   * up as it's made, so the step's own Apply saves it, as are the server's suggestions
   * once they're filled in.
   */
  import {nextTick, onMounted, watch} from 'vue';
  import MappingTable from '@/modules/import/mapping/MappingTable.vue';
  import MatchingSection from '@/modules/import/mapping/MatchingSection.vue';
  import {provideMappingContext} from '@/modules/import/mapping/useMappingPanel';
  import type {ImportStep, MappingValues} from '@/modules/import/mapping/types';
  import type {TextExpanderTriggers} from '@craftcms/ui/components/text-expander/text-expander';
  import {initialMappingState, type StepMappingPayload} from './step-mapping';

  const {data, step, editable} = defineProps<{
    data: StepMappingPayload;
    step: ImportStep;
    editable: boolean;
  }>();

  const emit = defineEmits<{change: [values: MappingValues]}>();

  const {values, suggestedMap, templateErrors, panelId} = provideMappingContext(
    {
      ...initialMappingState(data),
      sourceDataCols: data.sourceDataCols ?? [],
      templateTextExpanderTriggers: (data.templateTextExpanderTriggers ??
        []) as TextExpanderTriggers,
      editable,
      // read when a nested mapping opens, so it posts the step as it is by then
      get step() {
        return step;
      },
    }
  );

  function emitChange(): void {
    emit('change', JSON.parse(JSON.stringify(values)) as MappingValues);
  }

  watch(values, emitChange, {deep: true});

  // the suggestions are shown as mapped, so they're the step's until they're changed
  onMounted(() => {
    if (Object.keys(suggestedMap).length) {
      emitChange();
    }
  });

  /**
   * Shows the server's errors for the templates, keyed by the column's path joined with
   * dots, and focuses the first invalid one.
   */
  async function showTemplateErrors(
    errors: Record<string, string[]>
  ): Promise<void> {
    templateErrors.value = errors;
    await nextTick();
    document
      .querySelector<HTMLElement>(
        `[data-mapping-template-invalid="${panelId}"]`
      )
      ?.focus();
  }

  defineExpose({showTemplateErrors});
</script>

<template>
  <div class="grid gap-lg">
    <MappingTable :cols="data.destinationCols ?? []" inset />
    <MatchingSection :cols="data.destinationCols ?? []" />
  </div>
</template>
