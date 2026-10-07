<script setup lang="ts">
  /**
   * One panel of `DetailsTabs`: a header naming the tab, with its status and
   * actions, above the tab's content. The default slot replaces the content,
   * for a tab that renders a named slot of `DetailsTabs`.
   */
  import {t} from '@craftcms/ui';
  import type {DetailsTab} from './DetailsTabs.vue';

  defineProps<{
    tab: DetailsTab;
    componentProps: Record<string, unknown>;
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
      <h3 class="text-md/4">{{ tab.label }}</h3>
    </div>

    <div class="flex items-center gap-1">
      <span v-if="tab.statusData" class="flex items-center gap-1">
        <craft-status
          :status="tab.statusData.indicator"
          :label="tab.statusData.label"
        ></craft-status>
        <span class="text-xs/4 text-neutral-text-quiet">
          {{ tab.statusData.label }}
        </span>
      </span>
      <component
        :is="tab.headerActionsComponent"
        v-if="tab.headerActionsComponent"
        v-bind="componentProps"
      />
      <craft-button
        type="button"
        icon="xmark-large"
        :aria-label="t('Close {tab}', {tab: tab.label})"
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
        v-if="tab.component"
        :is="tab.component"
        v-bind="componentProps"
      />
    </div>
  </slot>
</template>
