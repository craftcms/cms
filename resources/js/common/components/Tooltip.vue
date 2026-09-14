<script setup lang="ts">
  withDefaults(
    defineProps<{
      id?: string;
      icon?: string;
      placement?: string;
    }>(),
    {
      id: () => `tooltip-${Math.random().toString(36).substring(2, 9)}`,
      icon: 'circle-info',
      placement: 'top',
    }
  );
</script>

<template>
  <craft-button type="button" :id="id" variant="plain" size="zero" icon inherit>
    <slot name="icon">
      <craft-icon :name="icon"></craft-icon>
    </slot>
  </craft-button>

  <craft-tooltip :placement="placement" :for="id">
    <slot></slot>
  </craft-tooltip>
</template>

<style scoped lang="scss"></style>
