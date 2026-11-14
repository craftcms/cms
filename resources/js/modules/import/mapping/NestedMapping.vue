<script setup lang="ts">
  /**
   * A container column's mapping, as a slideout panel.
   *
   * Opened with `openSlideoutWith()` — see `nested-mapping.ts` for why. The panel
   * edits its own copy of the screen's trees and hands them back on Apply, so
   * canceling leaves the screen behind untouched.
   */
  import '@craftcms/ui/components/checkbox/checkbox';
  import {computed} from 'vue';
  import {t} from '@craftcms/ui';
  import {ignoreModelValueInitialization} from '@/modules/ui/runtime';
  import CpContainer from '@/common/components/CpContainer.vue';
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
    templateTextExpanderTriggers: context.templateTextExpanderTriggers,
    editable: context.editable,
    step: context.step,
    apply: context.apply,
  });

  const keepPath = computed(() => keepFlagPath(context.col));

  /** Each entry type's columns, named only when there are several. */
  const tableGroups = computed(() =>
    context.groups.map((group) => ({
      label:
        context.groups.length > 1
          ? (group.providerName ?? context.fieldName)
          : null,
      cols: group.destinationCols,
    }))
  );

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
  <craft-field-group class="py-md">
    <MappingTable :groups="tableGroups" />

    <CpContainer v-if="context.col.canKeepMissingNestedElements">
      <craft-checkbox
        :label="
          t('Keep existing nested elements missing from the imported data.')
        "
        .checked="keepMissingChecked"
        .disabled="!context.editable"
        @model-value-changed="onKeepMissingChanged"
      ></craft-checkbox>
    </CpContainer>
  </craft-field-group>
</template>
