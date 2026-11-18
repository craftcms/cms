<script setup lang="ts">
  import {useWidgetHeader} from './useWidgetHeader';

  /**
   * Renders its content, such as a button, in the widget's header, before
   * the widget's settings button, centered on the title's first line.
   * Outside a dashboard widget, it renders nothing.
   */
  const {actionsTarget} = useWidgetHeader();
</script>

<template>
  <Teleport v-if="actionsTarget" :to="actionsTarget">
    <slot />
  </Teleport>
</template>
