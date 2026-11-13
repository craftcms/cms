<script setup lang="ts">
  import {t} from '@craftcms/ui';
  import '@craftcms/ui/components/timeline-item/timeline-item';
  import {computed, useSlots} from 'vue';
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

  defineSlots<{
    /** More of the event, below its changes, such as a comment’s text. */
    default?(): unknown;
    /** Beside the event’s time, before it, such as an actions menu. */
    'meta-start'?(): unknown;
  }>();

  const slots = useSlots();
  const hasBody = computed(
    () =>
      props.event.source.label !== 'Craft' ||
      props.event.changes.length > 0 ||
      Boolean(slots.default)
  );

  // the operation becomes a link, so it's split out of the translated sentence
  const originParts = computed(() =>
    t('via {origin}: {operation}', {
      origin: props.event.origin ?? '',
      operation: '\u0000',
    }).split('\u0000')
  );

  function sentenceFragment(text: string | null): string {
    return text === null
      ? ''
      : text.charAt(0).toLocaleLowerCase() + text.slice(1);
  }
</script>

<template>
  <article :data-activity-event="event.id" class="activity-timeline__event">
    <craft-timeline-item .last="last">
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
        <span v-if="event.origin" class="activity-timeline__origin">
          <template v-if="event.operation">
            {{ originParts[0]
            }}<a v-if="event.operation.url" :href="event.operation.url">{{
              event.operation.label
            }}</a
            ><template v-else>{{ event.operation.label }}</template
            >{{ originParts[1] }}
          </template>
          <template v-else>{{
            t('via {origin}', {origin: event.origin})
          }}</template>
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

        <slot />
      </div>

      <div slot="meta" class="activity-timeline__meta">
        <slot name="meta-start" />
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

  .activity-timeline__description,
  .activity-timeline__origin {
    margin-inline-start: 0.25em;
  }

  .activity-timeline__meta {
    display: flex;
    align-items: center;
    gap: var(--c-spacing-sm);
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
