<script setup lang="ts">
  /**
   * A settings screen's details column: an Info tab showing any sidebar
   * controls (the default slot) above the server's metadata HTML (ID, usages, …).
   */
  import {t} from '@craftcms/ui';
  import DetailsTabs, {
    type DetailsTab,
  } from '@/common/components/DetailsTabs.vue';
  import MetadataDetailsContent from '@/common/components/MetadataDetailsContent.vue';
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
        <MetadataDetailsContent :html="html">
          <template v-if="$slots.default" #default>
            <slot />
          </template>
        </MetadataDetailsContent>
      </template>
    </DetailsTabs>
  </LayoutSlot>
</template>
