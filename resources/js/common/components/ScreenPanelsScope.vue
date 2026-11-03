<script setup lang="ts">
  /**
   * Adds the `ScreenPanel`s inside it to `panels`, rather than to the screen
   * they're on, such as a preview's editor showing a page's panels. Key it by
   * which registry it's given, so its panels move when that changes.
   */
  import {provide} from 'vue';
  import {
    ScreenPanelsKey,
    type ScreenPanels,
  } from '@/common/composables/screenPanels';

  const props = defineProps<{panels?: ScreenPanels | null}>();

  if (props.panels) {
    provide(ScreenPanelsKey, props.panels);
  }
</script>

<template>
  <slot></slot>
</template>
