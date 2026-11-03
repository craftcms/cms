<script setup lang="ts">
  import '@craftcms/ui/components/timeline-item/timeline-item';
  import MarkdownHtml from '@/common/components/MarkdownHtml.vue';
  import MessageBubble from '@/common/components/MessageBubble.vue';
  import {usePhotoColors} from '@/common/composables/usePhotoColors';
  import ActivityTimelineActor from '@/modules/activity/components/ActivityTimelineActor.vue';

  type WorkflowReviewData = CraftCms.Cms.Workflow.Data.WorkflowReviewData;
  type WorkflowRun = WorkflowReviewData['runs'][number];
  type TimelineEvent =
    | WorkflowRun['submission']
    | WorkflowRun['stages'][number]['events'][number];

  const props = withDefaults(
    defineProps<{
      event: TimelineEvent;
      last?: boolean;
    }>(),
    {last: false}
  );

  const photoColors = usePhotoColors(() => props.event.actor.photoColors);
</script>

<template>
  <craft-timeline-item :last.prop="last">
    <craft-icon slot="marker" :name="event.icon" />
    <div slot="heading" class="workflow-review-summary">
      <ActivityTimelineActor
        :actor="event.actor"
        :impersonator="event.impersonator"
      />
      {{ event.description }}
    </div>
    <time
      slot="meta"
      :datetime="event.occurredAt"
      :title="event.formattedOccurredAt.full"
    >
      {{ event.formattedOccurredAt.time }}
    </time>
    <MessageBubble v-if="event.noteHtml" :style="photoColors">
      <MarkdownHtml :html="event.noteHtml" />
    </MessageBubble>
  </craft-timeline-item>
</template>

<style scoped>
  .workflow-review-summary {
    min-width: 0;
  }
</style>
