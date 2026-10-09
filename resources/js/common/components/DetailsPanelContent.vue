<script setup lang="ts">
  /**
   * One panel of `DetailsPanels`: a header naming the panel, with its status and
   * actions, above the panel's content. The default slot replaces the content,
   * for a panel that renders a named slot of `DetailsPanels`.
   */
  import {t} from '@craftcms/ui';
  import type {DetailsPanel} from './DetailsPanels.vue';

  defineProps<{
    panel: DetailsPanel;
    componentProps: Record<string, unknown>;
    /** The heading's id, which names the panel and takes focus when it opens. */
    headingId: string;
  }>();
  const emit = defineEmits<{
    (event: 'close'): void;
  }>();
</script>

<template>
  <div
    class="py-1 px-lg border-b border-b-quiet flex justify-between items-center min-h-(--cp-header-height)"
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
        <span class="text-xs/4 text-neutral-text-quiet">
          {{ panel.statusData.label }}
        </span>
      </span>
      <component
        :is="panel.headerActionsComponent"
        v-if="panel.headerActionsComponent"
        v-bind="componentProps"
      />
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
  <slot>
    <div class="p-lg">
      <component
        v-if="panel.component"
        :is="panel.component"
        v-bind="componentProps"
      />
    </div>
  </slot>
</template>
