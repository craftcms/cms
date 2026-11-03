<script setup lang="ts">
  /** One step's section of a plan's review, headed by its number, importer type and source. */
  import type {ReviewStep} from './review';

  defineProps<{
    step: ReviewStep;
  }>();
</script>

<template>
  <section class="import-review-step">
    <header class="import-review-step__header">
      <h3 class="m-0">{{ step.number }}. {{ step.label }}</h3>
      <code v-if="step.source" class="import-review-step__source">{{
        step.source
      }}</code>
    </header>
    <slot></slot>
  </section>
</template>

<style scoped>
  .import-review-step {
    display: flex;
    flex-direction: column;
    gap: var(--c-spacing-md);
  }

  .import-review-step__header {
    display: flex;
    flex-direction: column;
    gap: var(--c-spacing-xs);
  }

  .import-review-step__source {
    color: var(--c-text-quiet);
    overflow-wrap: anywhere;
  }
</style>
