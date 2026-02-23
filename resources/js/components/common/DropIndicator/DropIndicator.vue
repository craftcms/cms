<script setup lang="ts">
  import type {Edge} from '@atlaskit/pragmatic-drag-and-drop-hitbox/types';

  const props = defineProps<{
    edge: Edge | null;
  }>();
</script>

<template>
  <div
    v-if="edge"
    :class="{
      'drop-indicator': true,
      'drop-indicator--top': edge === 'top',
      'drop-indicator--bottom': edge === 'bottom',
      'drop-indicator--left': edge === 'left',
      'drop-indicator--right': edge === 'right',
      'drop-indicator--horizontal': ['top', 'bottom'].includes(edge),
      'drop-indicator--vertical': ['left', 'right'].includes(edge),
    }"
  />
</template>

<style scoped lang="scss">
  .drop-indicator {
    position: absolute;
    left: 0;
    width: 2000px; // Large enough to span any table
    height: 2px;
    background-color: var(--c-color-primary-bg-normal, #2563eb);
    pointer-events: none;
    z-index: 10;
  }

  .drop-indicator--top {
    top: -1px; // Account for border-collapse
  }

  .drop-indicator--bottom {
    bottom: -1px; // Account for border-collapse
  }
</style>
