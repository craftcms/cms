<script setup lang="ts">
  withDefaults(
    defineProps<{
      variant?: 'danger' | 'success' | 'warning' | 'info';
      appearance?: 'emphasis' | 'default' | 'outline' | 'plain';
    }>(),
    {variant: 'info', appearance: 'default'}
  );
</script>

<template>
  <div
    :class="{
      callout: true,
      'callout--danger': variant === 'danger',
      'callout--info': variant === 'info',
      'callout--success': variant === 'success',
      'callout--warning': variant === 'warning',
      'callout--emphasis': appearance === 'emphasis',
      'callout--default': appearance === 'default',
      'callout--outline': appearance === 'cp:outline',
      'callout--plain': appearance === 'plain',
    }"
  >
    <slot></slot>
  </div>
</template>

<style scoped lang="scss">
  .callout {
    background-color: var(--c-callout-fill);
    padding: var(--c-callout-padding, var(--c-spacing-md));
    border: 1px solid var(--c-callout-border-color);
    color: var(--c-callout-text);
    border-radius: var(--c-callout-radius);
  }

  .callout--danger {
    --c-callout-border-color: var(--c-color-danger-border-normal);
    --c-callout-text: var(--c-color-danger-on-normal);
    --c-callout-fill: var(--c-color-danger-fill-normal);
  }
</style>
