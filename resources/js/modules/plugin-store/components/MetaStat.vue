<script setup lang="ts">
  const {label} = defineProps<{label?: string}>();
</script>

<template>
  <div class="meta-stat">
    <dt>
      <slot name="label">{{ label }}</slot>
    </dt>
    <dd>
      <slot></slot>
    </dd>
  </div>
</template>

<style scoped>
  .meta-stat {
    padding-block: var(--c-spacing-md);
    border-block-end: 1px solid var(--c-color-border-quiet);
  }

  dt {
    color: var(--c-text-quiet);
  }

  dd {
    margin-block: var(--c-spacing-xs) 0;
    margin-inline: 0;
    font-weight: 500;
  }
</style>
