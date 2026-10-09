<script setup lang="ts">
  import '@craftcms/ui/components/timeline-item/timeline-item';
  import type {ActivityEvent} from '@/modules/activity/composables/useActivityTimeline';
  import ActivityCommentCard from '@/modules/activity/components/ActivityCommentCard.vue';
  import ActivityTimelineEvent from '@/modules/activity/components/ActivityTimelineEvent.vue';

  const props = defineProps<{
    event: ActivityEvent;
    elementType: string;
    elementId: number | null;
    siteId: number | null;
    last: boolean;
    noteHtml: string | null;
  }>();
</script>

<template>
  <ActivityTimelineEvent
    v-if="!noteHtml"
    :event="event"
    :element-type="elementType"
    :element-id="elementId"
    :site-id="siteId"
    :last="last"
  />

  <article
    v-else
    :data-activity-event="event.id"
    class="activity-timeline__event"
  >
    <craft-timeline-item :last="last">
      <craft-icon slot="marker" :name="event.icon ?? 'wave-pulse'" />
      <ActivityCommentCard
        :actor="event.actor"
        :impersonator="event.impersonator"
        :description-html="event.description.html"
        :description-text="event.description.text"
        :html="noteHtml"
        :occurred-at="event.occurredAt"
        :formatted-occurred-at="event.formattedOccurredAt"
      />
    </craft-timeline-item>
  </article>
</template>
