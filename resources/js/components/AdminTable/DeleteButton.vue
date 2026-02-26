<script setup lang="ts">
  import {t} from '@craftcms/cp/utilities/translate.ts.mjs';

  const emit = defineEmits<{
    (e: 'click'): void;
  }>();
  const props = withDefaults(
    defineProps<{
      label?: string;
      icon?: string;
    }>(),
    {label: t('Delete item'), icon: 'x'}
  );

  function handleSubmit() {}
</script>

<template>
  <craft-button
    type="button"
    @click="emit('click')"
    size="small"
    appearance="plain"
    variant="danger"
    v-bind="$attrs"
  >
    <craft-icon name="x" :label="label"></craft-icon>
  </craft-button>
</template>

<style scoped lang="scss"></style>
