<script setup lang="ts">
  import ActivityTimeline from '@/modules/activity/components/ActivityTimeline.vue';

  defineProps<{
    activityTimelineUrl: string;
    elementType: string;
    elementId: number;
    siteId: number | null;
  }>();
</script>

<template>
  <craft-pane appearance="raised" padding="none">
    <ActivityTimeline
      active
      :url="activityTimelineUrl"
      :element-type="elementType"
      :element-id="elementId"
      :site-id="siteId"
    />
  </craft-pane>
</template>
