<script setup lang="ts">
  import {t} from '@craftcms/ui';
  import type {ActivityEvent} from '@/modules/activity/composables/useActivityTimeline';
  import ActivityTimelineActor from './ActivityTimelineActor.vue';
  import ActivityTimelineChanges from './ActivityTimelineChanges.vue';
  import ActivityTimelineComment from './ActivityTimelineComment.vue';

  defineProps<{
    event: ActivityEvent;
    elementType: string;
    elementId: number | null;
    siteId: number | null;
    last: boolean;
  }>();

  const emit = defineEmits<{
    updated: [event: ActivityEvent];
  }>();

  function sentenceFragment(text: string | null): string {
    return text === null
      ? ''
      : text.charAt(0).toLocaleLowerCase() + text.slice(1);
  }
</script>

<template>
  <article
    :data-activity-event="event.id"
    class="activity-timeline__event"
    :class="{'activity-timeline__event--last': last}"
  >
    <span class="activity-timeline__marker" aria-hidden="true">
      <craft-icon :name="event.icon ?? 'wave-pulse'" />
    </span>

    <div class="activity-timeline__content">
      <div
        v-if="!event.comment || event.comment.deleted"
        class="activity-timeline__heading"
      >
        <div class="activity-timeline__summary">
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
      </div>

      <div
        v-if="event.source.label !== 'Craft'"
        class="activity-timeline__source"
      >
        {{ event.source.label }}
      </div>

      <ActivityTimelineComment
        v-if="event.comment"
        :event="event"
        :element-type="elementType"
        :element-id="elementId"
        :site-id="siteId"
        @updated="emit('updated', $event)"
      />

      <ActivityTimelineChanges
        v-else-if="event.changes.length"
        :changes="event.changes"
      />

      <div
        v-if="!event.comment || event.comment.deleted"
        class="activity-timeline__event-footer"
      >
        <time
          :datetime="event.occurredAt"
          :title="event.formattedOccurredAt.full"
        >
          {{ event.formattedOccurredAt.time }}
        </time>
      </div>
    </div>
  </article>
</template>

<style scoped>
  .activity-timeline__event {
    position: relative;
    display: grid;
    grid-template-columns: 1.75rem minmax(0, 1fr);
    gap: var(--c-spacing-sm);
    padding-block: var(--c-spacing-sm);
  }

  .activity-timeline__event::after {
    position: absolute;
    inset-block: 2.5rem 0;
    inset-inline-start: 0.85rem;
    width: 1px;
    background: var(--c-color-neutral-border-quiet);
    content: '';
  }

  .activity-timeline__event--last::after {
    display: none;
  }

  .activity-timeline__marker {
    z-index: 1;
    display: grid;
    width: 1.75rem;
    height: 1.75rem;
    place-items: center;
    border: 1px solid var(--c-color-neutral-border-normal);
    border-radius: 50%;
    background: var(--c-color-neutral-fill-quiet);
  }

  .activity-timeline__content,
  .activity-timeline__summary {
    min-width: 0;
  }

  .activity-timeline__heading {
    min-width: 0;
  }

  .activity-timeline__description {
    margin-inline-start: 0.25em;
  }

  .activity-timeline__source,
  .activity-timeline__event-footer {
    color: var(--c-text-quiet);
    font-size: var(--c-text-xs);
  }

  .activity-timeline__event-footer {
    display: flex;
    justify-content: flex-end;
    margin-block-start: var(--c-spacing-xs);
  }
</style>
