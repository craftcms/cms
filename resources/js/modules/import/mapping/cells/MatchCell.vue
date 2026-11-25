<script setup lang="ts">
  /**
   * Whether existing elements are found by a mapped destination column, as well as it
   * being filled from the incoming data. Only a mapped column that can be matched on
   * gets the choice.
   */
  import {t} from '@craftcms/ui';
  import {computed} from 'vue';
  import CheckboxCell from '@/common/table/cells/CheckboxCell.vue';
  import {isMatchKey, setMatchKey} from '../matching';
  import {getAt, hasMapping} from '../paths';
  import {useMappingContext, type MappingColumn} from '../types';

  const {col} = defineProps<{col: MappingColumn}>();

  const context = useMappingContext();

  /** A container has no value of its own to match on: its nested columns decide that in its panel. */
  const canMatch = computed(
    () =>
      !col.isContainer &&
      Boolean(col.canBeMatchCriteria) &&
      hasMapping(getAt(context.values.map, col.prefixedHandleAsArray))
  );
</script>

<template>
  <CheckboxCell
    v-if="canMatch"
    :checked="isMatchKey(context.values, col)"
    :label="t('Match on {field}', {field: col.label})"
    :disabled="!context.editable"
    @change="setMatchKey(context.values, col, $event)"
  />
</template>
