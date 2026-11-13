<script setup lang="ts">
  /**
   * How an import plan's latest run went, or how it's going, with a link to each
   * step's part of it.
   */
  import '@craftcms/ui/components/callout/callout';
  import {t} from '@craftcms/ui';
  import {computed} from 'vue';
  import RunIndicator from './RunIndicator.vue';
  import {stepRunId, type RunState} from './run-state';

  const {run, stepUids} = defineProps<{
    run: RunState;
    /** The plan's steps in order, so the link goes to the first one the run concerns. */
    stepUids: string[];
  }>();

  const variant = computed(() => {
    switch (run.status) {
      case 'running':
        return 'warning';
      case 'completed':
        return 'success';
      case 'failed':
        return 'danger';
    }

    return 'neutral';
  });

  const title = computed(() => {
    switch (run.status) {
      case 'pending':
        return t('The import is waiting to run.');
      case 'running':
        return run.progress < 0
          ? t('The import is running.')
          : t('The import is running: {percent, number}% done.', {
              percent: run.progress,
            });
      case 'completed':
        return t('The last run completed.');
      case 'failed':
        return t('The last run finished with errors.');
    }

    return '';
  });

  const failedItems = computed(() =>
    Object.values(run.steps).reduce(
      (count, step) => count + (step.errorCount ?? 0),
      0
    )
  );

  const detail = computed(() => {
    if (run.status !== 'failed') {
      return null;
    }

    return failedItems.value
      ? t(
          '{count, plural, =1 {# item} other {# items}} couldn’t be imported.',
          {count: failedItems.value}
        )
      : t('It stopped before it finished.');
  });

  /** The first failed step when there is one, else the first step the run includes. */
  const target = computed(() => {
    const included = stepUids.filter((uid) => uid in run.steps);
    const uid =
      run.status === 'failed'
        ? (included.find((uid) => run.steps[uid]?.status === 'failed') ??
          included[0])
        : included[0];

    return uid ? `#${stepRunId(uid)}` : null;
  });
</script>

<template>
  <craft-callout
    class="run-callout"
    :variant="variant"
    appearance="fill"
    role="status"
  >
    <RunIndicator slot="icon" :status="run.status" :progress="run.progress" />
    <div slot="title" class="font-bold">{{ title }}</div>
    <template v-if="detail">{{ detail }}</template>
    <a v-if="target" slot="action" :href="target">{{ t('View details') }}</a>
  </craft-callout>
</template>

<style scoped>
  .run-callout {
    margin-block-end: var(--c-spacing-lg);
    --c-run-indicator-stroke-width: 2px;
  }
</style>
