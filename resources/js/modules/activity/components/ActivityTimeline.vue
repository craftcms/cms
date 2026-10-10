<script setup lang="ts">
  import {t} from '@craftcms/ui';
  import {ref} from 'vue';
  import ActivityTimelineComment from './ActivityTimelineComment.vue';
  import {
    type ActivityEvent,
    useActivityTimeline,
    type ActivityTimelineProps,
  } from '@/modules/activity/composables/useActivityTimeline';

  const props = defineProps<ActivityTimelineProps>();
  const timeline = ref<HTMLElement | null>(null);
  const {
    addOrUpdateEvent,
    dayGroups,
    events,
    hasLoaded,
    load,
    scrollToEnd,
    status,
  } = useActivityTimeline(props, timeline);

  async function commentCreated(event: ActivityEvent): Promise<void> {
    addOrUpdateEvent(event);
    await scrollToEnd();
  }
</script>

<template>
  <div class="activity-timeline">
    <div ref="timeline" class="activity-timeline__scroll" scroll-region>
      <div
        v-if="!hasLoaded && status === 'loading'"
        class="activity-timeline__status"
        role="status"
      >
        <craft-spinner />
        <span class="visually-hidden">{{ t('Loading activity') }}</span>
      </div>

      <div
        v-else-if="!hasLoaded && status === 'error'"
        class="activity-timeline__status"
        role="alert"
      >
        <p>{{ t('Couldn’t load activity.') }}</p>
        <craft-button
          type="button"
          size="small"
          variant="outline"
          data-activity-retry
          @click="load"
        >
          {{ t('Retry') }}
        </craft-button>
      </div>

      <p
        v-else-if="hasLoaded && events.length === 0"
        class="activity-timeline__status"
      >
        {{ t('No activity has been recorded yet.') }}
      </p>

      <div v-else-if="hasLoaded" class="activity-timeline__rail">
        <section
          v-for="group in dayGroups"
          :key="group.key"
          :data-activity-day="group.key"
        >
          <h4 class="activity-timeline__day">
            <span class="activity-timeline__day-label">{{ group.label }}</span>
          </h4>
          <component
            v-for="(event, index) in group.events"
            :key="event.id"
            :is="event.component"
            v-bind="event.props"
            :event="event"
            :element-type="elementType"
            :element-id="elementId"
            :site-id="siteId"
            :last="index === group.events.length - 1"
            @updated="addOrUpdateEvent"
          />
        </section>
      </div>
    </div>

    <div
      v-if="hasLoaded"
      class="activity-timeline__composer"
      data-activity-comment-composer
    >
      <ActivityTimelineComment
        :element-type="elementType"
        :element-id="elementId"
        :site-id="siteId"
        @created="commentCreated"
      />
    </div>
  </div>
</template>

<style scoped>
  .activity-timeline {
    display: flex;
    flex-direction: column;
    block-size: 100%;
  }

  .activity-timeline__scroll {
    min-height: 8rem;
    padding-block: var(--c-spacing-lg);
    padding-inline: var(--cp-container-padding);
    overflow-y: auto;
  }

  .activity-timeline__status {
    display: grid;
    justify-items: center;
    gap: var(--c-spacing-sm);
    padding-block: var(--c-spacing-xl);
    padding-inline: var(--c-spacing-md);
    text-align: center;
    color: var(--c-text-quiet);
  }

  .activity-timeline__status p {
    margin: 0;
  }

  .activity-timeline__day {
    position: relative;
    text-align: center;
    margin-block: var(--c-spacing-md);
    color: var(--c-text-quiet);
    font-size: var(--c-text-xs);
    font-weight: 600;
  }

  /* Out through the scroll area's padding to the pane's edges. */
  .activity-timeline__day::before {
    content: '';
    position: absolute;
    inset-block-start: 50%;
    inset-inline: calc(var(--cp-container-padding) * -1);
    border-block-start: 1px solid var(--c-color-border-quiet);
  }

  .activity-timeline__day-label {
    position: relative;
    display: inline-block;
    padding-block: var(--c-spacing-xs);
    padding-inline: var(--c-spacing-md);
    border: 1px solid var(--c-color-border-quiet);
    border-radius: var(--c-radius-full);
    background-color: var(--c-surface-default);
  }

  section:first-of-type .activity-timeline__day {
    margin-block-start: 0;
  }

  .activity-timeline__composer {
    margin-block-start: calc(var(--c-spacing-lg) * -1);
    padding-block: var(--c-spacing-lg);
    padding-inline: var(--cp-container-padding);
    background: linear-gradient(
      to bottom,
      color-mix(in srgb, var(--c-surface-default) 0%, transparent),
      var(--c-surface-default) var(--c-spacing-lg)
    );
    position: relative;
    z-index: 1;
  }
</style>
