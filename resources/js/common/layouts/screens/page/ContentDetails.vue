<script setup lang="ts">
  /**
   * The resizable column after the content. Its root is the element the
   * resizer measures, so the shell reads it through a template ref.
   */
  import {t} from '@craftcms/ui/utilities/translate';
  import {useId} from 'vue';
  import LayoutSlotOutlet from '@/common/components/LayoutSlotOutlet.vue';
  import ResizeHandle from '@/common/components/ResizeHandle.vue';
  import type {UseResizableReturn} from '@/common/composables/useResizable';
  import type {ScreenSlots} from '../types';

  defineProps<{
    resizer: UseResizableReturn;
  }>();

  defineSlots<Pick<ScreenSlots, 'content-details'>>();

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
    <div :id="detailsId" class="cp-details sticky top-0">
      <LayoutSlotOutlet name="content-details">
        <slot name="content-details"></slot>
      </LayoutSlotOutlet>
    </div>
  </div>
</template>

<style scoped lang="css">
  .cp-details {
    display: grid;
    align-items: start;
    gap: var(--c-spacing-md);
    height: 100%;
    min-block-size: 0;
  }

  /* The height the tabs get; craft-tabs scrolls its own panels within it. */
  .cp-details :deep(craft-tabs) {
    block-size: 100%;
    min-block-size: 0;
  }
</style>
