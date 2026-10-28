<script setup lang="ts">
  /**
   * The details column beside the content: the screen's panels, or what the
   * page put in `content-details` when there are none. With panels as well,
   * that content becomes a panel of its own.
   *
   * Its root is the element the shell measures to size the pane, and it
   * exposes the resizable column inside it, so the shell reads both through a
   * template ref.
   */
  import {t} from '@craftcms/ui/utilities/translate';
  import {useTemplateRef} from 'vue';
  import DetailsPanels from '@/common/components/DetailsPanels.vue';
  import LayoutSlotOutlet from '@/common/components/LayoutSlotOutlet.vue';
  import ScreenPanel from '@/common/components/ScreenPanel.vue';
  import {
    DETAILS_PANEL_ID,
    type ScreenPanels,
  } from '@/common/composables/screenPanels';
  import type {UseResizableReturn} from '@/common/composables/useResizable';
  import ContentDetails from './ContentDetails.vue';

  defineProps<{
    panels: ScreenPanels;
    resizer: UseResizableReturn;
    /** Whether the page's details content goes in a panel of its own. */
    detailsContentIsPanel: boolean;
    /** Pairs `#details` with `.details` for the legacy element editor. */
    legacyIds?: boolean;
  }>();

  defineSlots<{
    /** The page's `content-details`. */
    details(): unknown;
  }>();

  const column = useTemplateRef<{$el: HTMLElement}>('column');

  defineExpose({
    /** The resizable column, which the shell's resizer measures. */
    column: (): HTMLElement | undefined => column.value?.$el,
  });
</script>

<template>
  <aside :id="legacyIds ? 'details' : undefined" class="cp-content__details">
    <div class="cp-content__details-pane" :class="{details: legacyIds}">
      <ContentDetails ref="column" :resizer="resizer">
        <DetailsPanels
          v-if="panels.panels.value.length > 0"
          :registry="panels"
          sync-location-hash
        />
        <LayoutSlotOutlet v-else name="content-details">
          <slot name="details"></slot>
        </LayoutSlotOutlet>
      </ContentDetails>
      <ScreenPanel
        v-if="detailsContentIsPanel"
        :id="DETAILS_PANEL_ID"
        :label="t('Details')"
        icon="circle-info"
      >
        <LayoutSlotOutlet name="content-details">
          <slot name="details"></slot>
        </LayoutSlotOutlet>
      </ScreenPanel>
    </div>
  </aside>
</template>

<style scoped lang="css">
  /* Runs from wherever the pane starts to the viewport's bottom, stopping
     short only once the page's end brings the panel's own bottom into view. */
  .cp-content__details-pane {
    position: sticky;
    inset-block-start: 0;
    height: calc(
      100dvh - var(--cp-debug-bar-height, 0px) - var(--cp-details-offset, 0px) -
        var(--cp-details-end-gap, 0px)
    );
  }
</style>
