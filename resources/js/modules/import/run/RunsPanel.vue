<script setup lang="ts">
  /**
   * An import plan's latest runs in its details sidebar, newest first, with a link
   * to them all when there are more. Its rail icon is marked while the latest run has failed, so that's
   * noticed with the panel closed.
   */
  import {t} from '@craftcms/ui';
  import {computed} from 'vue';
  import CpLink from '@/common/components/CpLink.vue';
  import FormattedDate from '@/common/components/Date.vue';
  import ScreenPanel from '@/common/components/ScreenPanel.vue';
  import RunIndicator from './RunIndicator.vue';
  import {
    outcomeLabels,
    runCounts,
    runDuration,
    statusLabels,
  } from './run-format';
  import type {ItemOutcome, RunState} from './run-state';

  const {runs, count, url} = defineProps<{
    /** The latest runs, newest first. */
    runs: RunState[];
    /** How many runs the plan has kept. */
    count: number;
    /** Where they're all listed. */
    url: string;
  }>();

  /** Its status, then the outcomes its items came to, leaving out those none did. */
  function outcomeSummary(run: RunState): string {
    return [
      statusLabels[run.status](),
      ...(Object.entries(runCounts(run)) as Array<[ItemOutcome, number]>)
        .filter(([, n]) => n > 0)
        .map(([outcome, n]) => `${outcomeLabels[outcome]()}: ${n}`),
    ].join(' · ');
  }

  const alert = computed(() =>
    runs[0]?.status === 'failed' ? t('The latest run failed.') : null
  );
</script>

<template>
  <ScreenPanel
    id="runs"
    :label="t('Activity')"
    icon="wave-pulse"
    :order="1"
    :alert="alert"
    closed-by-default
  >
    <template v-if="count > runs.length" #header-actions>
      <CpLink :href="url" class="text-sm me-md">
        {{ t('View all activity') }}
        <craft-icon name="circle-arrow-right" />
      </CpLink>
    </template>
    <div v-if="!runs.length" class="runs-panel__section text-quiet">
      {{ t('This import plan hasn’t been run yet.') }}
    </div>
    <div v-else class="runs-panel__section">
      <ul class="runs-panel__runs">
        <li v-for="run in runs" :key="run.id" class="runs-panel__run">
          <RunIndicator :status="run.status" :progress="run.progress" />
          <a :href="run.url"><FormattedDate :value="run.dateCreated" /></a>
          <span class="text-quiet">{{ runDuration(run) ?? '' }}</span>
          <small class="runs-panel__run-outcomes text-quiet">{{
            outcomeSummary(run)
          }}</small>
        </li>
      </ul>
    </div>
  </ScreenPanel>
</template>

<style scoped>
  .runs-panel__section {
    display: grid;
    gap: var(--c-spacing-md);
    padding-block: var(--c-spacing-lg);
    padding-inline: var(--cp-container-padding);
  }

  .runs-panel__runs {
    margin: 0;
    padding: 0;
    list-style: none;
  }

  /* the indicator, the date and how long it took, with its outcomes beneath */
  .runs-panel__run {
    display: grid;
    grid-template-columns: auto minmax(0, 1fr) auto;
    align-items: center;
    gap: 0 var(--c-spacing-md);
    padding-block: var(--c-spacing-lg);
    font-weight: bold;
  }

  .runs-panel__run + .runs-panel__run {
    border-block-start: 1px solid var(--c-color-border-quiet);
  }

  .runs-panel__run-outcomes {
    grid-column: 2 / -1;
    font-size: var(--c-text-sm);
    font-weight: 400;
  }
</style>
