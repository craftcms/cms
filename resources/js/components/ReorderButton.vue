<script setup lang="ts">
  import {t} from '@craftcms/cp';

  withDefaults(
    defineProps<{
      label?: string;
    }>(),
    {label: t('Reorder')}
  );
</script>

<template>
  <craft-button size="small" icon class="cursor-move" appearance="plain">
    <craft-icon name="custom-icons/grip-dots" :label="label"></craft-icon>
  </craft-button>
</template>

<style scoped lang="scss"></style>
