<script setup lang="ts">
  /**
   * The resizable column after the content. Its root is the element the
   * resizer measures, so the shell reads it through a template ref.
   */
  import {t} from '@craftcms/ui/utilities/translate';
  import {useId} from 'vue';
  import ResizeHandle from '@/common/components/ResizeHandle.vue';
  import type {UseResizableReturn} from '@/common/composables/useResizable';

  defineProps<{
    resizer: UseResizableReturn;
  }>();

  defineSlots<{
    /** The screen's details panels, or the page's details content. */
    default(): unknown;
  }>();

  // `aria-controls` needs a real id, and `#details` is taken — legacy CSS
  // pins it to 350px, overriding the grid track.
  const detailsId = `cp-content-details-${useId()}`;
</script>

<template>
  <div class="relative h-full">
    <ResizeHandle
      class="cp-details-resize-handle"
      :resizer="resizer"
      :label="t('Resize details')"
      :controls="detailsId"
    />
    <div :id="detailsId" class="cp-details">
      <h2 class="sr-only">{{ t('Details') }}</h2>
      <slot></slot>
    </div>
  </div>
</template>

<style scoped lang="css">
  .cp-details {
    display: grid;
    /* Panels fit the column, rather than widening it past the pane. */
    grid-template-columns: minmax(0, 1fr);
    grid-template-rows: minmax(0, 1fr);
    align-items: start;
    gap: var(--c-spacing-md);
    height: 100%;
    overflow-y: auto;
  }
</style>
