<script setup lang="ts">
  /**
   * The frame for side-by-side `CustomizeSourcesColumn`s in a modal: it fills
   * the modal, and each column scrolls on its own.
   */
  import {useTemplateRef} from 'vue';

  const el = useTemplateRef<HTMLElement>('el');

  defineExpose({el});
</script>

<template>
  <div ref="el" class="cs-body">
    <slot />
  </div>
</template>

<style scoped lang="scss">
  // Not wrapping: a wrapping row is as tall as its tallest column, which would
  // stretch past the modal instead of scrolling — and the narrow layout shows
  // one column at a time rather than stacking them.
  .cs-body {
    display: flex;
    flex-flow: row nowrap;
    align-items: stretch;
    container-type: inline-size;
    flex: 1 1 0;
    min-height: 0;
    /* The modal's pane drops its border with this, and panes inside, like a
       condition group's, would inherit that. */
    --c-pane-border-width: initial;
  }

  :slotted(craft-spinner) {
    margin: auto;
  }
</style>
