<script setup lang="ts">
  import {t} from '@craftcms/ui';
  import ActivityTimelineChangeValue from './ActivityTimelineChangeValue.vue';
  import type {ActivityChange} from '@/modules/activity/composables/useActivityTimeline';

  defineProps<{
    changes: ActivityChange[];
  }>();
</script>

<template>
  <ul class="activity-timeline__change-list">
    <li
      v-for="(change, index) in changes"
      :key="index"
      class="activity-timeline__change"
    >
      <strong class="activity-timeline__change-label">
        {{ change.label }}
      </strong>
      <dl class="activity-timeline__change-comparison">
        <div>
          <dt>{{ t('Before') }}</dt>
          <dd><ActivityTimelineChangeValue :value="change.old" /></dd>
        </div>
        <div>
          <dt>{{ t('After') }}</dt>
          <dd><ActivityTimelineChangeValue :value="change.new" /></dd>
        </div>
      </dl>
    </li>
  </ul>
</template>

<style scoped>
  .activity-timeline__change-list {
    container-type: inline-size;
    margin: 0;
    padding: 0;
    list-style: none;
  }

  .activity-timeline__change {
    padding-block: var(--c-spacing-sm);
    border-block-start: 1px solid var(--c-color-neutral-border-quiet);
  }

  .activity-timeline__change:first-child {
    border-block-start: 0;
  }

  .activity-timeline__change-label,
  .activity-timeline__change-comparison dd {
    overflow-wrap: anywhere;
  }

  .activity-timeline__change-comparison {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: var(--c-spacing-sm);
    margin-block: var(--c-spacing-xs) 0;
  }

  .activity-timeline__change-comparison div {
    min-width: 0;
  }

  .activity-timeline__change-comparison dt {
    color: var(--c-text-quiet);
    font-size: var(--c-text-xs);
    font-weight: 600;
  }

  .activity-timeline__change-comparison dd {
    margin: var(--c-spacing-xs) 0 0;
  }

  @container (min-width: 32rem) {
    .activity-timeline__change {
      display: grid;
      grid-template-columns: minmax(8rem, 1fr) minmax(0, 2fr);
      gap: var(--c-spacing-md);
    }

    .activity-timeline__change-comparison {
      margin-block-start: 0;
    }
  }
</style>
