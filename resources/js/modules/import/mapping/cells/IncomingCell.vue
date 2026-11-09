<script setup lang="ts">
  /**
   * Where a destination column's value comes from: a source column, a template, or,
   * for a container column, a nested mapping of its own. A template is entered in the
   * Preview column.
   */
  import '@craftcms/ui/components/badge/badge';
  import {Color, t} from '@craftcms/ui';
  import {computed, ref, watch} from 'vue';
  import SelectRichCell from '@/common/table/cells/SelectRichCell.vue';
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
   * another is chosen. The select shows the badge too while that option is selected.
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

  const importSettings = computed<FieldMappingSetting[]>(() =>
    mapValue.value !== '' ? (col.importSettings ?? []) : []
  );
</script>

<template>
  <NestedMappingButton v-if="col.isContainer" :col="col" />
  <template v-else>
    <SelectRichCell
      :value="mapValue"
      :options="context.sourceDataCols"
      :label="col.label"
      :disabled="!context.editable"
      searchable
      @change="choose"
    >
      <template #option-badge="{option}">
        <craft-badge
          v-if="suggestedColumn !== null && option.value === suggestedColumn"
          slot="badge"
          :fill="Color.Accent"
          :size.attr="'small'"
          no-prefix
          >{{ t('Suggested') }}</craft-badge
        >
      </template>
    </SelectRichCell>
    <ImportSettings
      v-if="importSettings.length"
      :col="col"
      :settings="importSettings"
    />
  </template>
</template>
