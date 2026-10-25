<script setup lang="ts">
  /**
   * One panel of `DetailsPanels`: a header naming the panel, with its status
   * and the `header-actions` slot, above the default slot's content.
   */
  import {t} from '@craftcms/ui';
  import type {DetailsPanel} from './detailsPanels';

  defineProps<{
    panel: DetailsPanel;
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
      <h3 :id="panel.headingId" class="text-md/4" tabindex="-1">
        {{ panel.label }}
      </h3>
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
      <slot name="header-actions"></slot>
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
    class="grow basis-0 min-h-0"
    :class="
      panel.scrollsItself
        ? 'flex flex-col overflow-hidden'
        : 'cp-inner-scroller'
    "
  >
    <slot></slot>
  </div>
</template>
