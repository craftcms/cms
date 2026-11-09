<script setup lang="ts">
  /**
   * A step's mapping, edited in place rather than in its own panel. Each change is handed
   * up as it's made, so the step's own Apply saves it.
   */
  import {watch} from 'vue';
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

  const {values} = provideMappingContext({
    ...initialMappingState(data),
    sourceDataCols: data.sourceDataCols ?? [],
    templateTextExpanderTriggers: (data.templateTextExpanderTriggers ??
      []) as TextExpanderTriggers,
    editable,
    step,
  });

  watch(
    values,
    () => emit('change', JSON.parse(JSON.stringify(values)) as MappingValues),
    {deep: true}
  );
</script>

<template>
  <div class="grid gap-lg">
    <MappingTable :cols="data.destinationCols ?? []" inset />
    <MatchingSection :cols="data.destinationCols ?? []" />
  </div>
</template>
