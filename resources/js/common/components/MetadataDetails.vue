<script setup lang="ts">
  /**
   * A settings screen's details column: an Info tab showing any sidebar
   * controls (the default slot) above the server's metadata HTML (ID, usages, …).
   */
  import {t} from '@craftcms/ui';
  import DetailsTabs, {
    type DetailsTab,
  } from '@/common/components/DetailsTabs.vue';
  import DynamicHtmlRenderer from '@/common/components/DynamicHtmlRenderer.vue';
  import LayoutSlot from '@/common/components/LayoutSlot.vue';

  defineProps<{
    html: string | null | undefined;
  }>();

  const tabs: DetailsTab[] = [
    {id: 'info', label: t('Info'), icon: 'circle-info', slot: 'info'},
  ];
</script>

<template>
  <LayoutSlot v-if="html || $slots.default" name="content-details">
    <DetailsTabs :tabs="tabs">
      <template #info>
        <div class="p-lg">
          <slot />
          <hr v-if="html && $slots.default" class="my-lg" />
          <DynamicHtmlRenderer v-if="html" :html="html" />
        </div>
      </template>
    </DetailsTabs>
  </LayoutSlot>
</template>
