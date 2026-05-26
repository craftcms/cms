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
  <craft-button
    type="button"
    :id="id"
    appearance="plain"
    size="zero"
    icon
    variant="inherit"
  >
    <slot name="icon">
      <craft-icon :name="icon"></craft-icon>
    </slot>
  </craft-button>

  <c-tooltip :placement="placement" :for="id">
    <slot></slot>
  </c-tooltip>
</template>

<style scoped lang="scss"></style>
