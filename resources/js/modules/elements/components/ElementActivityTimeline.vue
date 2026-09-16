<script setup lang="ts">
  import ActivityTimeline from '@/modules/activity/components/ActivityTimeline.vue';
  import type {ElementEditPayload} from '@/modules/elements/composables/useElementEditor';

  defineProps<{
    payload: ElementEditPayload;
    activeTabId: string | null;
    refreshToken: number;
  }>();
</script>

<template>
  <ActivityTimeline
    :active="activeTabId === 'activity'"
    :url="payload.activityTimelineUrl!"
    :element-type="payload.elementType"
    :element-id="payload.canonicalId"
    :site-id="payload.siteId"
    :page-url="payload.activityPageUrl"
    :refresh-token="refreshToken"
  />
</template>
