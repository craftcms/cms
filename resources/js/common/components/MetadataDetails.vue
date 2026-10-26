<script setup lang="ts">
  /**
   * A settings screen's details: an Info panel showing any sidebar controls
   * (the default slot) above the server's metadata HTML (ID, usages, …).
   */
  import {t} from '@craftcms/ui';
  import MetadataDetailsContent from '@/common/components/MetadataDetailsContent.vue';
  import ScreenPanel from '@/common/components/ScreenPanel.vue';

  defineProps<{
    html: string | null | undefined;
  }>();
</script>

<template>
  <ScreenPanel
    v-if="html || $slots.default || $slots.actions"
    id="info"
    :label="t('Info')"
    icon="circle-info"
  >
    <MetadataDetailsContent :html="html">
      <template v-if="$slots.default" #default>
        <slot />
      </template>
      <template v-if="$slots.actions" #actions>
        <slot name="actions" />
      </template>
    </MetadataDetailsContent>
  </ScreenPanel>
</template>
