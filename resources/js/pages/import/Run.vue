<script setup lang="ts">
  /**
   * One run of an import plan: how it went overall, what each step run did with
   * its items and why the failed ones failed, and the changes it made.
   */
  import '@craftcms/ui/components/progress-bar/progress-bar';
  import {t} from '@craftcms/ui';
  import {router} from '@inertiajs/vue3';
  import {useIntervalFn} from '@vueuse/core';
  import {computed, shallowRef, watch} from 'vue';
  import CpContainer from '@/common/components/CpContainer.vue';
  import type {ImportStep} from '@/modules/import/mapping/types';
  import StepCardLayout from '@/modules/import/steps/StepCardLayout.vue';
  import {stepSettingRows} from '@/modules/import/steps/step-settings';
  import type {StepSummary} from '@/modules/import/steps/step-slideout';
  import ItemResultsTable from '@/modules/import/preview/ItemResultsTable.vue';
  import type {ItemResultRow} from '@/modules/import/preview/plan-preview';
  import RunIndicator from '@/modules/import/run/RunIndicator.vue';
  import RunStepItems from '@/modules/import/run/RunStepItems.vue';
  import RunSummary from '@/modules/import/run/RunSummary.vue';
  import {
    stepRunId,
    type RunItems,
    type RunState,
    type StepRunState,
  } from '@/modules/import/run/run-state';

  const props = defineProps<{
    run: RunState;
    /** The plan's steps as they are now, by uid, for previewing failed items against. */
    previewSteps: Record<string, ImportStep>;
    /** The failed items each step would now skip, by step uid; loaded after the page. */
    skippedInPreview?: Record<string, number[]>;
    /** The page's title. */
    title: string;
    /** The first page of each step's items, by step uid. */
    items: Record<string, RunItems>;
    /** Each of the plan's steps at a glance, by uid; missing for a step that's since been removed. */
    stepSummaries?: Record<string, StepSummary>;
  }>();

  /** The items of a step that's waiting or has only just started, which it has none of yet: its table shows only where they'll be. */
  const waitingItems: ItemResultRow[] = [];

  /** A step's settings, as the plan's list of steps shows them. */
  function settingRows(uid: string): Array<{label: string; value: string}> {
    return stepSettingRows(props.previewSteps[uid], props.stepSummaries?.[uid]);
  }

  const unfinished = computed(() =>
    ['pending', 'running'].includes(props.run.status)
  );

  const poll = useIntervalFn(
    () => router.reload({only: ['run', 'items']}),
    2000,
    {immediate: false}
  );
  watch(unfinished, (value) => (value ? poll.resume() : poll.pause()), {
    immediate: true,
  });

  const percentFormat = new Intl.NumberFormat(
    document.documentElement.lang || undefined,
    {style: 'percent'}
  );

  /** What a step run's status band says once it's done, or while it waits its turn. */
  function stepStatus(step: StepRunState): string {
    switch (step.status) {
      case 'pending':
        return t('Waiting to run');
      case 'running':
        return t('Running');
      case 'successful':
        return t('Successful');
      case 'failed':
        return step.counts.failed
          ? t(
              '{count, plural, =1 {# item} other {# items}} couldn’t be imported.',
              {count: step.counts.failed}
            )
          : t('This step stopped before it finished.');
    }

    return '';
  }
</script>

<template>
  <CpContainer>
    <RunSummary class="import-run__summary" :run="run" />

    <ol class="import-run__steps">
      <li
        v-for="(step, uid, index) in run.steps"
        :id="stepRunId(String(uid))"
        :key="uid"
        class="import-run__step-row"
        :aria-labelledby="`step-run-label-${uid}`"
      >
        <section
          class="import-run__step"
          :class="`import-run__step--${step.status}`"
        >
          <div class="import-run__step-status">
            <RunIndicator :status="step.status" :progress="step.progress" />
            <span class="font-semibold">{{ stepStatus(step) }}</span>
            <div
              v-if="step.status === 'running'"
              class="import-run__step-progress"
            >
              <span class="import-run__step-percent text-quiet">{{
                percentFormat.format(step.progress / 100)
              }}</span>
              <craft-progress-bar
                class="import-run__step-progress-bar"
                :progress="step.progress"
                :label="t('Progress')"
                smooth
              ></craft-progress-bar>
            </div>
          </div>
          <StepCardLayout
            :number="index + 1"
            :label="step.label"
            :source="step.source.name"
            :settings="settingRows(String(uid))"
            :label-id="`step-run-label-${uid}`"
          >
            <RunStepItems
              v-if="items[uid]"
              class="import-run__items"
              :run-id="run.id"
              :step-uid="String(uid)"
              :initial="items[uid]"
              :preview-step="previewSteps[uid] ?? null"
              :skipped-in-preview="
                skippedInPreview ? (skippedInPreview[uid] ?? []) : null
              "
            />
            <ItemResultsTable
              v-else-if="step.status === 'pending' || step.status === 'running'"
              class="import-run__items"
              flush
              loading
              :items="waitingItems"
              :row-id="() => ''"
              :match-columns="[]"
              :counts="null"
              :total="0"
              :matching="0"
              :page="0"
              :page-size="3"
              :placeholder-rows="3"
              :can-filter="() => false"
              :actions="() => []"
            />
          </StepCardLayout>
        </section>
      </li>
    </ol>
  </CpContainer>
</template>

<style scoped>
  .import-run__summary {
    margin-block-end: var(--c-spacing-lg);
  }

  .import-run__steps {
    --c-run-indicator-size: calc(20rem / 16);
    display: grid;
    gap: var(--c-spacing-lg);
    margin: 0;
    padding: 0;
    list-style: none;
  }

  .import-run__step-row {
    scroll-margin-block-start: var(--c-spacing-xl);
  }

  /* each step run sits on a tint of how it went, with its status above its card; the tint reaches past the card as a spread shadow, so the card lines up with the summary */
  .import-run__step {
    border-radius: var(--c-radius-lg);
    background-color: var(--_status-fill, transparent);
    box-shadow: 0 0 0 4px var(--_status-fill, transparent);
  }

  .import-run__step--successful {
    --_status-fill: var(--c-color-success-fill-quiet);
    --step-card-outline-color: var(--c-color-success-border-quiet);
    --_status-color: var(--c-color-success-on-quiet);
  }

  .import-run__step--running {
    --_status-fill: var(--c-color-accent-fill-quiet);
    --step-card-outline-color: var(--c-color-accent-border-quiet);
    --_status-color: var(--c-color-accent-on-quiet);
  }

  .import-run__step--failed {
    --_status-fill: var(--c-color-danger-fill-quiet);
    --step-card-outline-color: var(--c-color-danger-border-quiet);
    --_status-color: var(--c-color-danger-on-quiet);
  }

  /* edge to edge of its column from the top of the card, with its chips and rows lined up with the rail's padding */
  .import-run__items {
    --cp-container-padding: var(--c-spacing-lg);
    margin-inline: calc(var(--c-spacing-lg) * -1);
    margin-block-end: calc(var(--c-spacing-lg) * -1);
  }

  .import-run__step-status {
    display: flex;
    align-items: center;
    gap: var(--c-spacing-md);
    padding-block: var(--c-spacing-sm) calc(var(--c-spacing-sm) + 2px);
    padding-inline: var(--c-spacing-md);
    color: var(--_status-color, inherit);
  }

  /* at the end of the row, where it doesn't push the status aside as it fills */
  .import-run__step-progress {
    display: flex;
    flex: 0 1 24rem;
    align-items: center;
    gap: var(--c-spacing-md);
    margin-inline-start: auto;
  }

  .import-run__step-progress-bar {
    --c-progress-bar-height: 0.25rem;
    flex: 1;
  }

  /* as wide as 100%, so the bar doesn't move as it counts up */
  .import-run__step-percent {
    min-inline-size: 5ch;
    font-variant-numeric: tabular-nums;
    text-align: end;
  }
</style>
