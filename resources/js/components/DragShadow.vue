<script setup lang="ts">
  defineProps<{
    height?: number;
    position?: 'top' | 'bottom';
  }>();
</script>

<template>
  <div
    v-if="height"
    class="drag-shadow"
    :class="`drag-shadow--${position ?? 'bottom'}`"
    :style="{height: `${height}px`}"
  >
  </div>
</template>

<style scoped lang="scss">
  .drag-shadow {
    flex-shrink: 0;
    align-self: stretch;
    border-radius: var(--c-radius-md);
    background-color: var(--c-color-neutral-fill-quiet);
    border: 1px dashed var(--c-color-neutral-border-quiet);
    box-sizing: border-box;
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
    overflow: hidden;
  }

  .drag-shadow--top {
    order: -1;
  }

  .drag-shadow--bottom {
    order: 1;
  }
</style>
