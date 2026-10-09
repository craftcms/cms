<script setup lang="ts">
  import '@craftcms/ui/components/timeline-item/timeline-item';
  import ActivityCommentCard from '@/modules/activity/components/ActivityCommentCard.vue';
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
</script>

<template>
  <craft-timeline-item :last.prop="last">
    <craft-icon slot="marker" :name="event.icon" />
    <div v-if="!event.noteHtml" slot="heading" class="workflow-review-summary">
      <ActivityTimelineActor
        :actor="event.actor"
        :impersonator="event.impersonator"
      />
      {{ event.description }}
    </div>
    <time
      v-if="!event.noteHtml"
      slot="meta"
      :datetime="event.occurredAt"
      :title="event.formattedOccurredAt.full"
    >
      {{ event.formattedOccurredAt.time }}
    </time>
    <ActivityCommentCard
      v-if="event.noteHtml"
      class="workflow-review-comment"
      :actor="event.actor"
      :impersonator="event.impersonator"
      :description-text="event.description"
      :html="event.noteHtml"
      :occurred-at="event.occurredAt"
      :formatted-occurred-at="event.formattedOccurredAt"
    />
  </craft-timeline-item>
</template>

<style scoped>
  .workflow-review-summary {
    min-width: 0;
  }
</style>
