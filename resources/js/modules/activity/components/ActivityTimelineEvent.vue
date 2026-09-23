<script setup lang="ts">
  import {t} from '@craftcms/ui';
  import '@craftcms/ui/components/timeline-item/timeline-item';
  import {computed} from 'vue';
  import type {ActivityEvent} from '@/modules/activity/composables/useActivityTimeline';
  import ActivityTimelineActor from './ActivityTimelineActor.vue';
  import ActivityTimelineChanges from './ActivityTimelineChanges.vue';

  const props = defineProps<{
    event: ActivityEvent;
    elementType: string;
    elementId: number | null;
    siteId: number | null;
    last: boolean;
  }>();

  const hasBody = computed(
    () => props.event.source.label !== 'Craft' || props.event.changes.length > 0
  );

  function sentenceFragment(text: string | null): string {
    return text === null
      ? ''
      : text.charAt(0).toLocaleLowerCase() + text.slice(1);
  }
</script>

<template>
  <article :data-activity-event="event.id" class="activity-timeline__event">
    <craft-timeline-item :last="last">
      <craft-icon slot="marker" :name="event.icon ?? 'wave-pulse'" />

      <div slot="heading" class="activity-timeline__summary">
        <ActivityTimelineActor
          :actor="event.actor"
          :impersonator="event.impersonator"
        />

        <span
          v-if="event.description.html"
          class="activity-timeline__description"
          v-html="event.description.html"
        />
        <span v-else class="activity-timeline__description">
          {{ sentenceFragment(event.description.text) }}
        </span>
      </div>

      <div v-if="hasBody" class="activity-timeline__body">
        <div
          v-if="event.source.label !== 'Craft'"
          class="activity-timeline__source"
        >
          {{ event.source.label }}
        </div>

        <ActivityTimelineChanges
          v-if="event.changes.length"
          :changes="event.changes"
        />
      </div>

      <div slot="meta">
        <time
          :datetime="event.occurredAt"
          :title="event.formattedOccurredAt.full"
        >
          {{ event.formattedOccurredAt.time }}
        </time>
      </div>
    </craft-timeline-item>
  </article>
</template>

<style scoped>
  .activity-timeline__summary {
    min-width: 0;
  }

  .activity-timeline__description {
    margin-inline-start: 0.25em;
  }

  .activity-timeline__body {
    display: flex;
    flex-direction: column;
    gap: var(--c-spacing-sm);
    --c-card-padding-inline: var(--c-spacing-md);
    --c-card-padding-block: var(--c-spacing-md);
  }

  .activity-timeline__source {
    color: var(--c-text-quiet);
    font-size: var(--c-text-xs);
  }
</style>
