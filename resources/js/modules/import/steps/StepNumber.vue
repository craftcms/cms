<script setup lang="ts">
  /** A step's place in its plan, in a ring, wherever the step is named. */
  const {size = 'medium'} = defineProps<{
    number: number;
    size?: 'small' | 'medium';
  }>();
</script>

<template>
  <span
    class="step-number"
    :class="`step-number--${size}`"
    aria-hidden="true"
    >{{ number }}</span
  >
</template>

<style scoped>
  /* `--step-number-color` tints the ring and its number, as a selected tab does */
  .step-number {
    display: inline-grid;
    flex: none;
    place-items: center;
    border-radius: var(--c-radius-full);
    border: 1px solid var(--step-number-color, var(--c-color-border-quiet));
    color: var(--step-number-color, var(--c-color-on-quiet));
    font-weight: var(--font-weight-semibold);
    font-variant-numeric: tabular-nums;
  }

  .step-number--medium {
    inline-size: calc(var(--c-spacing) * 6);
    block-size: calc(var(--c-spacing) * 6);
    font-size: var(--c-text-sm);
  }

  .step-number--small {
    inline-size: 1.25rem;
    block-size: 1.25rem;
    font-size: var(--c-text-xs);
  }
</style>
