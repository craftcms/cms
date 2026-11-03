<script setup lang="ts">
  /**
   * One step's field mapping, inline on the steps screen. Edits the trees it's given in
   * place and reports each change, so the step carries them when the plan is saved.
   *
   * When it's searchable, its fields can be narrowed to a search, or to what's mapped.
   */
  import '@craftcms/ui/components/input/input';
  import {computed, ref, watch} from 'vue';
  import {t} from '@craftcms/ui';
  import MappingTable from '@/modules/import/mapping/MappingTable.vue';
  import MatchSummary from '@/modules/import/mapping/MatchSummary.vue';
  import {useMappingEditor} from '@/modules/import/mapping/useMappingEditor';
  import type {ImportStep, MappingValues} from '@/modules/import/mapping/types';
  import type {StepMappingData} from './step-mapping';
  import MappingLoading from './MappingLoading.vue';

  const props = defineProps<{
    mapping: StepMappingData;
    step: ImportStep;
    editable: boolean;
    /** The server's errors about the templates, keyed by the column's path joined with dots. */
    templateErrors: Record<string, string[]>;
    /** Whether the mapping is being reloaded for a change to the step. */
    loading?: boolean;
    /** Leaves out the Mapping heading, for a container that already heads it. */
    headless?: boolean;
    /** Leaves out the summary of what's matched on, and its test. */
    hideMatchSummary?: boolean;
    /** Leaves out the Match column, for matching that's chosen somewhere else. */
    hideMatch?: boolean;
    /** Lets the fields be narrowed to a search, or to what's mapped. */
    searchable?: boolean;
  }>();

  const emit = defineEmits<{
    change: [values: MappingValues];
  }>();

  // a cell drops its template's errors once it's edited, so the editor keeps its own copy
  const templateErrors = ref({...props.templateErrors});
  watch(
    () => props.templateErrors,
    (next) => (templateErrors.value = {...next})
  );

  const {values} = useMappingEditor({
    values: props.mapping.values,
    suggestedMap: props.mapping.suggestedMap,
    sourceDataCols: props.mapping.sourceDataCols,
    templateTextExpanderTriggers: props.mapping.templateTextExpanderTriggers,
    editable: props.editable,
    step: () => props.step,
    templateErrors,
  });

  watch(
    values,
    () => emit('change', JSON.parse(JSON.stringify(values)) as MappingValues),
    {deep: true}
  );

  const filter = ref<'all' | 'mapped' | 'unmapped'>('all');
  const query = ref('');

  const filters = computed(() => [
    {value: 'all' as const, label: t('All')},
    {value: 'mapped' as const, label: t('Mapped')},
    {value: 'unmapped' as const, label: t('Not mapped')},
  ]);
</script>

<template>
  <div class="step-mapping-editor">
    <h3 v-if="!headless" class="step-mapping-editor__heading m-0">
      {{ t('Mapping') }}
    </h3>
    <MappingLoading v-if="loading" />
    <div
      :class="{
        'step-mapping-editor__body': true,
        'step-mapping-editor__stale': loading,
      }"
      :aria-busy="loading || undefined"
    >
      <template v-if="searchable">
        <div class="step-mapping-editor__toolbar">
          <craft-input
            class="step-mapping-editor__search"
            type="search"
            :placeholder="t('Search fields')"
            :label="t('Search fields')"
            label-sr-only
            .modelValue="query"
            @model-value-changed="
              query = ($event.target as any).modelValue ?? ''
            "
          ></craft-input>
          <div
            class="step-mapping-editor__filters"
            role="group"
            :aria-label="t('Show')"
          >
            <craft-button
              v-for="option in filters"
              :key="option.value"
              type="button"
              size="small"
              :variant="filter === option.value ? undefined : 'plain'"
              :aria-pressed="filter === option.value"
              @click="filter = option.value"
            >
              {{ option.label }}
            </craft-button>
          </div>
        </div>
        <MappingTable
          :cols="mapping.destinationCols"
          :hide-match="hideMatch"
          :filter="filter"
          :query="query"
        />
      </template>
      <MappingTable
        v-else
        :cols="mapping.destinationCols"
        :hide-match="hideMatch"
      />
      <MatchSummary
        v-if="!hideMatchSummary"
        :cols="mapping.destinationCols"
        :step="step"
        :target-name="mapping.targetName"
      />
    </div>
  </div>
</template>

<style scoped>
  .step-mapping-editor,
  .step-mapping-editor__body {
    display: flex;
    flex-direction: column;
    gap: var(--c-spacing-md);
  }

  .step-mapping-editor__toolbar {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: var(--c-spacing-sm) var(--c-spacing-md);
  }

  .step-mapping-editor__search {
    inline-size: 16rem;
    max-inline-size: 100%;
  }

  .step-mapping-editor__filters {
    display: flex;
    gap: var(--c-spacing-xs);
  }

  .step-mapping-editor__stale {
    opacity: 0.5;
    transition: opacity 0.15s;
  }

  /* styled as a field's label, since it heads the mapping as one heads its field */
  .step-mapping-editor__heading {
    font-size: var(--c-field-label-font-size, inherit);
    font-weight: var(--c-field-label-font-weight, bold);
    line-height: var(--c-field-label-line-height, inherit);
    color: var(--c-field-label-color, inherit);
  }
</style>
