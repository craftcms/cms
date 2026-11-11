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

  /** A read-only mapping still opens, so its nested columns can be looked at, but not changed. */
  const label = computed(() => {
    if (!hasNestedMapping.value) {
      return t('Map field');
    }

    return context.editable ? t('Edit mapping') : t('View mapping');
  });
</script>

<template>
  <craft-button
    ref="button"
    type="button"
    :variant="hasNestedMapping ? ButtonVariant.Fill : ButtonVariant.Dashed"
    :icon="hasNestedMapping ? (context.editable ? 'pencil' : 'eye') : 'plus'"
    .disabled="!context.editable && !hasNestedMapping"
    @click="context.openNested(col, button)"
  >
    {{ label }}
  </craft-button>
</template>
