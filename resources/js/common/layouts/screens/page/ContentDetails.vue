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
    /**
     * `v-show`, not `v-if`: the outlet is a teleport target and must stay in
     * the DOM so page-side content can mount before it flips this on.
     */
    visible: boolean;
    resizer: UseResizableReturn;
  }>();

  defineSlots<Pick<ScreenSlots, 'content-details'>>();

  // `aria-controls` needs a real id, and `#details` is taken — legacy CSS
  // pins it to 350px, overriding the grid track.
  const detailsId = `cp-content-details-${useId()}`;
</script>

<template>
  <aside v-show="visible" class="cp-content__details">
    <ResizeHandle
      class="cp-details-resize-handle"
      :resizer="resizer"
      :label="t('Resize details')"
      :controls="detailsId"
    />
    <div :id="detailsId" class="cp-details">
      <LayoutSlotOutlet name="content-details">
        <slot name="content-details"></slot>
      </LayoutSlotOutlet>
    </div>
  </aside>
</template>

<style scoped lang="css">
  .cp-content__details:has(craft-tabs[collapsed]) {
    --resize-handle-display: none;
  }

  .cp-details {
    display: grid;
    gap: var(--c-spacing-md);
    height: 100%;
    position: relative;
  }
</style>
