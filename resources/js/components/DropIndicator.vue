<script setup lang="ts">
  import type {Edge} from '@atlaskit/pragmatic-drag-and-drop-hitbox/types';
  import VarDump from '@/components/VarDump.vue';

  const props = defineProps<{
    edge?: Edge | null;
    contained?: boolean;
    inline?: boolean;
    visible?: boolean;
  }>();
</script>

<template>
  <div
    v-if="edge || inline"
    :class="{
      'drop-indicator': true,
      'drop-indicator--contained': contained,
      'drop-indicator--top': edge === 'top',
      'drop-indicator--bottom': edge === 'bottom',
      'drop-indicator--left': edge === 'left',
      'drop-indicator--right': edge === 'right',
      'drop-indicator--horizontal': !edge || ['top', 'bottom'].includes(edge),
      'drop-indicator--vertical': edge && ['left', 'right'].includes(edge),
      'drop-indicator--inline': inline,
      'drop-indicator--active': visible,
    }"
  />
</template>

<style scoped lang="scss">
  .drop-indicator {
    position: absolute;
    left: 0;
    width: 2000px; // Large enough to span any table
    height: calc(2rem / 16);
    background-color: var(--c-color-accent-fill-loud, #2563eb);
    pointer-events: none;
    z-index: 10;
  }

  .drop-indicator--contained {
    width: 100%;
  }

  .drop-indicator--inline {
    position: relative;
    top: auto;
    bottom: auto;
    background-color: transparent;
  }

  .drop-indicator--active {
    background-color: var(--c-color-accent-fill-loud, #2563eb);
  }

  .drop-indicator--top {
    top: -1px; // Account for border-collapse in tables

    &.drop-indicator--contained {
      top: 0;
    }
  }

  .drop-indicator--bottom {
    bottom: -1px; // Account for border-collapse in tables

    &.drop-indicator--contained {
      bottom: 0;
    }
  }
</style>
