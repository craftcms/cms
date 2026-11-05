<script setup lang="ts">
  /** Opens a container column's own mapping, such as a Matrix field's, in a nested panel. */
  import '@craftcms/ui/components/button/button';
  import {ButtonVariant, t} from '@craftcms/ui';
  import {computed, useTemplateRef} from 'vue';
  import {getAt} from '../paths';
  import {useMappingContext, type MappingColumn} from '../types';

  const {col} = defineProps<{col: MappingColumn}>();

  const context = useMappingContext();
  const button = useTemplateRef<HTMLElement>('button');

  const hasNestedMapping = computed(() => {
    const branch = getAt(context.values.map, col.prefixedHandleAsArray);

    return (
      branch !== null &&
      typeof branch === 'object' &&
      Object.keys(branch).length > 0
    );
  });
</script>

<template>
  <craft-button
    ref="button"
    type="button"
    :variant="ButtonVariant.Dashed"
    :icon="hasNestedMapping ? 'pencil' : 'plus'"
    .disabled="!context.editable"
    @click="context.openNested(col, button)"
  >
    {{ hasNestedMapping ? t('Edit mapping') : t('Map field') }}
  </craft-button>
</template>
