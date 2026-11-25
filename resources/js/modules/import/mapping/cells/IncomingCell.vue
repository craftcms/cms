<script setup lang="ts">
  /**
   * Where a destination column's value comes from: a source column, a template, or,
   * for a container column, a nested mapping of its own. A template is entered in the
   * Preview column.
   */
  import {t} from '@craftcms/ui';
  import {computed, ref, watch} from 'vue';
  import ComboboxCell from '@/common/table/cells/ComboboxCell.vue';
  import {
    useMappingContext,
    type FieldMappingSetting,
    type MappingColumn,
  } from '../types';
  import {useSourceMapping} from '../useSourceMapping';
  import ImportSettings from './ImportSettings.vue';
  import NestedMappingButton from './NestedMappingButton.vue';

  const {col} = defineProps<{col: MappingColumn}>();

  const context = useMappingContext();

  const {mapValue, isSuggested, choose} = useSourceMapping(() => col);

  /**
   * The column the server suggested, which its option goes on saying in the list once
   * another is chosen.
   */
  const suggestedColumn = ref<string | null>(null);

  watch(
    isSuggested,
    (suggested) => {
      if (suggested) {
        suggestedColumn.value = mapValue.value;
      }
    },
    {immediate: true}
  );

  /** The source columns, the suggested one saying so in its hint. */
  const options = computed(() =>
    context.sourceDataCols.map((option) =>
      suggestedColumn.value !== null && option.value === suggestedColumn.value
        ? {
            ...option,
            hint: option.hint
              ? t('Suggested · {hint}', {hint: option.hint})
              : t('Suggested'),
          }
        : option
    )
  );

  const importSettings = computed<FieldMappingSetting[]>(() =>
    mapValue.value !== '' ? (col.importSettings ?? []) : []
  );
</script>

<template>
  <NestedMappingButton v-if="col.isContainer" :col="col" />
  <template v-else>
    <ComboboxCell
      :value="mapValue"
      :options="options"
      :label="col.label"
      :disabled="!context.editable"
      @change="choose"
    />
    <ImportSettings
      v-if="importSettings.length"
      :col="col"
      :settings="importSettings"
    />
  </template>
</template>
