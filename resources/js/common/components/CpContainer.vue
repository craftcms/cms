<script setup lang="ts"></script>

<template>
  <div class="cp-container">
    <slot></slot>
    <slot name="full" class="cp-container__full"></slot>
  </div>
</template>

<style scoped lang="scss">
  .cp-container {
    --_gutter: var(--c-gutter, var(--c-spacing-lg));
    display: grid;
    grid-template-columns: var(--_gutter) 1fr var(--_gutter);
    gap: 0;
  }

  .cp-container > * {
    grid-column: 2;
  }

  .cp-container__full {
    grid-column: 1 / -1;
  }
</style>
