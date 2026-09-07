<script setup lang="ts">
  import {t} from '@craftcms/ui';
  import {ref} from 'vue';
  import CpLink from '@/common/components/CpLink.vue';
  import ActivityTimelineComment from './ActivityTimelineComment.vue';
  import ActivityTimelineEvent from './ActivityTimelineEvent.vue';
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
        <div v-if="pageUrl" class="activity-timeline__full-link">
          <CpLink :href="pageUrl">
            {{ t('View all activity') }}
            <craft-icon name="circle-arrow-right" />
          </CpLink>
        </div>

        <section
          v-for="group in dayGroups"
          :key="group.key"
          :data-activity-day="group.key"
        >
          <h4 class="activity-timeline__day">{{ group.label }}</h4>
          <ActivityTimelineEvent
            v-for="(event, index) in group.events"
            :key="event.id"
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
    max-height: 60vh;
  }

  .activity-timeline__scroll {
    min-height: 8rem;
    overflow-y: auto;
  }

  .activity-timeline__status {
    display: grid;
    justify-items: center;
    gap: var(--c-spacing-sm);
    padding: var(--c-spacing-xl) var(--c-spacing-md);
    text-align: center;
    color: var(--c-text-quiet);
  }

  .activity-timeline__status p {
    margin: 0;
  }

  .activity-timeline__rail,
  .activity-timeline__composer {
    padding: var(--c-spacing-md);
  }

  .activity-timeline__full-link {
    display: flex;
    align-items: center;
    margin-block-end: var(--c-spacing-md);
  }

  .activity-timeline__day {
    margin-block: var(--c-spacing-lg) var(--c-spacing-xs);
    color: var(--c-text-quiet);
    font-size: var(--c-text-xs);
    font-weight: 600;
  }

  section:first-of-type .activity-timeline__day {
    margin-block-start: 0;
  }

  .activity-timeline__composer {
    flex: none;
    border-block-start: 1px solid var(--c-color-neutral-border-quiet);
    background: var(--c-bg);
  }
</style>
