<script setup lang="ts"></script>

<template>
  <div class="cp-container">
    <slot></slot>
    <slot name="full" class="cp-container__full"></slot>
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

  .cp-container__full {
    grid-column: 1 / -1;
  }
</style>
