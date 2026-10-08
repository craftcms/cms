<script setup lang="ts"></script>

<template>
  <div class="cp-container">
    <slot></slot>
    <div v-if="$slots.full" class="cp-container__full">
      <slot name="full"></slot>
    </div>
  </div>
</template>

<style scoped lang="scss">
  .cp-container {
    display: grid;
    grid-template-columns: var(--cp-container-padding) minmax(0, 1fr) var(
        --cp-container-padding
      );
    gap: 0;
  }

  .cp-container > * {
    grid-column: 2;
  }

  /* `:deep` so a page can also put the class on its own direct children. */
  .cp-container > :deep(.cp-container__full) {
    grid-column: 1 / -1;
  }
</style>
