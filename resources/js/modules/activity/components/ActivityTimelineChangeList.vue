<script setup lang="ts">
  import ChangeComparison from '@/modules/ui/ChangeComparison.vue';
  import type {ActivityChange} from '@/modules/activity/composables/useActivityTimeline';

  defineProps<{
    changes: ActivityChange[];
  }>();

  /**
   * A change from one text to another, shown as a word diff. Any other change
   * shows its values before and after.
   */
  function diff(
    change: ActivityChange
  ): {current: string; proposed: string} | null {
    return typeof change.old === 'string' &&
      typeof change.new === 'string' &&
      change.old !== '' &&
      change.new !== ''
      ? {current: change.old, proposed: change.new}
      : null;
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
      <ChangeComparison
        :diff="diff(change)"
        :from="{value: change.old}"
        :to="{value: change.new}"
        class="activity-timeline__change-values"
      />
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

  .activity-timeline__change-label {
    overflow-wrap: anywhere;
    font-weight: var(--font-weight-semibold);
  }

  .activity-timeline__change-values {
    margin-block-start: var(--c-spacing-xs);
  }

  @container (min-width: 32rem) {
    .activity-timeline__change {
      display: grid;
      grid-template-columns: minmax(8rem, 1fr) minmax(0, 2fr);
      gap: var(--c-spacing-md);
    }

    .activity-timeline__change-values {
      margin-block-start: 0;
    }
  }
</style>
