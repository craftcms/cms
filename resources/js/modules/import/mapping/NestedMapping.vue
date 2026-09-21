<script setup lang="ts">
  /**
   * A container column's mapping, as a slideout panel.
   *
   * Opened with `openSlideoutWith()` — see `nested-mapping.ts` for why. The panel
   * edits its own copy of the screen's trees and hands them back on Apply, so
   * cancelling leaves the screen behind untouched.
   */
  import '@craftcms/ui/components/checkbox/checkbox';
  import {computed} from 'vue';
  import {t} from '@craftcms/ui';
  import {ignoreModelValueInitialization} from '@/modules/forms/runtime';
  import MappingTable from './MappingTable.vue';
  import {checkedValue, getAt, isChecked, keepFlagPath, setAt} from './paths';
  import {takeNestedMappingContext} from './nested-mapping';
  import {useMappingPanel} from './useMappingPanel';

  const props = defineProps<{
    contextId: string;
    title: string;
  }>();

  const context = takeNestedMappingContext(props.contextId);

  const {values} = useMappingPanel({
    title: props.title,
    values: context.values,
    suggestedMap: context.suggestedMap,
    sourceDataCols: context.sourceDataCols,
    editable: context.editable,
    step: context.step,
    nestedColsUrl: context.colsUrl,
    apply: context.apply,
  });

  const keepPath = computed(() => keepFlagPath(context.col));

  const keepMissingChecked = computed(() =>
    isChecked(getAt(values.keepMissingNestedElements, keepPath.value))
  );

  const onKeepMissingChanged = ignoreModelValueInitialization((event) => {
    setAt(
      values.keepMissingNestedElements,
      keepPath.value,
      checkedValue(event)
    );
  });
</script>

<template>
  <div>
    <craft-checkbox
      v-if="context.col.canKeepMissingNestedElements"
      :label="
        t('Keep existing nested elements missing from the imported data.')
      "
      .checked="keepMissingChecked"
      :disabled="!context.editable"
      @model-value-changed="onKeepMissingChanged"
    ></craft-checkbox>

    <section v-for="(group, index) in context.groups" :key="index">
      <h3>{{ group.providerName ?? context.fieldName }}</h3>
      <MappingTable :cols="group.destinationCols" />
    </section>
  </div>
</template>
