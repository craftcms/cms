<script setup lang="ts">
  /**
   * One panel of `DetailsPanels`: a header naming the panel, with its status
   * and actions, above the panel's content. The actions and content are
   * placeholders that the panel's `ScreenPanel` moves its own into.
   */
  import {t} from '@craftcms/ui';
  import type {ComponentPublicInstance} from 'vue';
  import type {DetailsPanel} from './detailsPanels';

  type PlaceholderRef = (
    element: Element | ComponentPublicInstance | null
  ) => void;

  defineProps<{
    panel: DetailsPanel;
    /** The heading's id, which names the panel and takes focus when it opens. */
    headingId: string;
    /**
     * Receive the elements the panel's header actions and content go in. Kept
     * the same from render to render, so they aren't let go of.
     */
    headerRef: PlaceholderRef;
    bodyRef: PlaceholderRef;
  }>();
  const emit = defineEmits<{
    (event: 'close'): void;
  }>();
</script>

<template>
  <div
    class="py-1 px-(--cp-container-padding) border-b border-b-quiet flex justify-between items-center min-h-(--cp-header-height)"
  >
    <div class="flex items-center gap-1">
      <h3 :id="headingId" class="text-md/4" tabindex="-1">{{ panel.label }}</h3>
    </div>

    <div class="flex items-center gap-1">
      <span v-if="panel.statusData" class="flex items-center gap-1">
        <craft-status
          :status="panel.statusData.indicator"
          :label="panel.statusData.label"
        ></craft-status>
        <span class="text-xs/4">
          {{ panel.statusData.label }}
        </span>
      </span>
      <div :ref="headerRef" class="contents"></div>
      <craft-button
        type="button"
        icon="xmark-large"
        :aria-label="t('Close {tab}', {tab: panel.label})"
        variant="plain"
        size="small"
        @click="emit('close')"
        flush="inline-end"
      ></craft-button>
    </div>
  </div>
  <div
    :ref="bodyRef"
    class="grow basis-0 min-h-0"
    :class="
      panel.scrollsItself
        ? 'flex flex-col overflow-hidden'
        : 'cp-inner-scroller'
    "
  ></div>
</template>
