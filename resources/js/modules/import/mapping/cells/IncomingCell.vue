<script setup lang="ts">
  /**
   * Where a destination column's value comes from: a source column, a template, or,
   * for a container column, a nested mapping of its own.
   */
  import '@craftcms/ui/components/badge/badge';
  import {t} from '@craftcms/ui';
  import {computed} from 'vue';
  import SelectRichCell from '@/common/table/cells/SelectRichCell.vue';
  import {getAt, isChecked, setAt} from '../paths';
  import {
    TEMPLATE_RULE,
    useMappingContext,
    type FieldMappingSetting,
    type MappingColumn,
  } from '../types';
  import ImportSettings from './ImportSettings.vue';
  import NestedMappingButton from './NestedMappingButton.vue';
  import TemplateField from './TemplateField.vue';

  const {col} = defineProps<{col: MappingColumn}>();

  const context = useMappingContext();

  const path = computed(() => col.prefixedHandleAsArray);

  const mapValue = computed(() => {
    const value = getAt(context.values.map, path.value);

    return value === null || value === undefined ? '' : String(value);
  });

  const isSuggested = computed(() =>
    Boolean(getAt(context.suggestedMap, path.value))
  );

  const importSettings = computed<FieldMappingSetting[]>(() =>
    mapValue.value !== '' ? (col.importSettings ?? []) : []
  );

  function choose(value: string): void {
    setAt(context.values.map, path.value, value);
    setAt(context.suggestedMap, path.value, false);

    // only a mapped column can be matched on, so unmapping it drops its match criterion too
    if (
      value === '' &&
      isChecked(getAt(context.values.matchCriteria, path.value))
    ) {
      setAt(context.values.matchCriteria, path.value, '');
    }
  }
</script>

<template>
  <NestedMappingButton v-if="col.isContainer" :col="col" />
  <template v-else>
    <SelectRichCell
      :value="mapValue"
      :options="context.sourceDataCols"
      :label="col.label"
      :disabled="!context.editable"
      @change="choose"
    >
      <template v-if="isSuggested" #help-text>
        <span slot="help-text" class="sr-only">{{ t('Suggested') }}</span>
      </template>
    </SelectRichCell>
    <div v-if="isSuggested" class="suggested">
      <craft-badge fill="blue" aria-hidden="true">{{
        t('Suggested')
      }}</craft-badge>
    </div>
    <TemplateField v-if="mapValue === TEMPLATE_RULE" :col="col" />
    <ImportSettings
      v-if="importSettings.length"
      :col="col"
      :settings="importSettings"
    />
  </template>
</template>

<style scoped>
  .suggested {
    display: flex;
    justify-content: flex-end;
    margin-block-start: var(--c-spacing-sm);
  }
</style>
