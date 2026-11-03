<script setup lang="ts">
  import type {ActivityEvent} from '@/modules/activity/composables/useActivityTimeline';
  import MarkdownHtml from '@/common/components/MarkdownHtml.vue';
  import MessageBubble from '@/common/components/MessageBubble.vue';
  import {usePhotoColors} from '@/common/composables/usePhotoColors';
  import ActivityTimelineEvent from '@/modules/activity/components/ActivityTimelineEvent.vue';

  const props = defineProps<{
    event: ActivityEvent;
    elementType: string;
    elementId: number | null;
    siteId: number | null;
    last: boolean;
    noteHtml: string | null;
  }>();

  const photoColors = usePhotoColors(() => props.event.actor.photoColors);
</script>

<template>
  <ActivityTimelineEvent
    :event="event"
    :element-type="elementType"
    :element-id="elementId"
    :site-id="siteId"
    :last="last"
  >
    <MessageBubble v-if="noteHtml" :style="photoColors">
      <MarkdownHtml :html="noteHtml" />
    </MessageBubble>
  </ActivityTimelineEvent>
</template>
