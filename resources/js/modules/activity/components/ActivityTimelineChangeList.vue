<script setup lang="ts">
  import {t} from '@craftcms/ui';
  import '@craftcms/ui/components/text-diff/text-diff';
  import ActivityTimelineChangeValue from './ActivityTimelineChangeValue.vue';
  import type {ActivityChange} from '@/modules/activity/composables/useActivityTimeline';

  defineProps<{
    changes: ActivityChange[];
  }>();

  /**
   * Whether a change is from one text to another, shown as a word diff. Any
   * other change shows its values before and after.
   */
  function isTextChange(
    change: ActivityChange
  ): change is ActivityChange & {old: string; new: string} {
    return (
      typeof change.old === 'string' &&
      typeof change.new === 'string' &&
      change.old !== '' &&
      change.new !== ''
    );
  }
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
      <craft-text-diff
        v-if="isTextChange(change)"
        :current="change.old"
        :proposed="change.new"
        class="activity-timeline__change-diff"
      ></craft-text-diff>
      <dl v-else class="activity-timeline__change-comparison">
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

  .activity-timeline__change-diff {
    margin-block-start: var(--c-spacing-xs);
  }

  .activity-timeline__change-label {
    font-weight: var(--font-weight-semibold);
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
    margin-block: var(--c-spacing-xs) 0;
    margin-inline: 0;
  }

  @container (min-width: 32rem) {
    .activity-timeline__change {
      display: grid;
      grid-template-columns: minmax(8rem, 1fr) minmax(0, 2fr);
      gap: var(--c-spacing-md);
    }

    .activity-timeline__change-comparison,
    .activity-timeline__change-diff {
      margin-block-start: 0;
    }
  }
</style>
