<script setup lang="ts">
  /**
   * One step in an import's list: what it imports and from where, its settings at a
   * glance, and its mapping, with buttons to edit each.
   */
  import '@craftcms/ui/components/badge/badge';
  import '@craftcms/ui/components/button/button';
  import '@craftcms/ui/components/progress-bar/progress-bar';
  import {t} from '@craftcms/ui';
  import {computed, shallowRef, useTemplateRef} from 'vue';
  import ActionMenu from '@/common/components/ActionMenu.vue';
  import type {ActionItems} from '@/common/types';
  import {hasMapping} from '@/modules/import/mapping/paths';
  import {matchModeLabel} from '@/modules/import/mapping/matching';
  import type {ImportStep} from '@/modules/import/mapping/types';
  import RunErrorsModal from '@/modules/import/run/RunErrorsModal.vue';
  import RunIndicator from '@/modules/import/run/RunIndicator.vue';
  import {stepRunId} from '@/modules/import/run/run-state';
  import type {StepRunState} from '@/modules/import/run/run-state';
  import type {StepSummary} from './step-slideout';
  import StepMappingSummary from './StepMappingSummary.vue';

  const {
    step,
    number,
    typeLabel,
    summary,
    editable,
    canToggle,
    invalid,
    opening,
    over,
    run = null,
  } = defineProps<{
    step: ImportStep;
    /** The step's place in the list, from 1. */
    number: number;
    typeLabel: string;
    summary: StepSummary | null;
    editable: boolean;
    /** Whether the step can be disabled or enabled; the last enabled one can't be. */
    canToggle: boolean;
    invalid: boolean;
    /** Which of the step's slideouts is opening. */
    opening: 'settings' | 'mapping' | null;
    /** Whether a dragged step is over this one. */
    over: boolean;
    /** Where the step got to in the plan's latest run, if it was part of one. */
    run?: StepRunState | null;
  }>();

  const emit = defineEmits<{
    edit: [opener: HTMLElement | null];
    editMapping: [opener: HTMLElement | null];
    toggleEnabled: [];
    remove: [];
  }>();

  const showingErrors = shallowRef(false);

  const settingsButton = useTemplateRef<HTMLElement>('settingsButton');
  const mappingButton = useTemplateRef<HTMLElement>('mappingButton');

  const labelId = computed(() => `step-label-${step.uid}`);
  const errorId = computed(() => `step-error-${step.uid}`);

  /** What the step imports and from where, e.g. “Entries — people.csv”. */
  const description = computed(() =>
    [typeLabel, step.source].filter(Boolean).join(' — ')
  );

  const sourceParts = computed(() => step.source?.split(/(?<=\/)/) ?? []);

  /** The step as it's named to screen reader users, e.g. “1. Entries — people.csv”. */
  const name = computed(() => `${number}. ${description.value}`);

  /** The step's settings as the server summed them up, plus what it does with rows that match. */
  const settingsRows = computed(() => {
    const settings = Array.isArray(step.settings) ? {} : (step.settings ?? {});
    const matching = Object.values(
      (settings.matchCriteria ?? {}) as Record<string, unknown>
    ).some((value) => hasMapping(value));
    const mode = settings.matchMode as string | null | undefined;

    return [
      ...(summary?.settings ?? []),
      ...(matching && mode && matchModeLabel(mode)
        ? [{label: t('If matched'), value: matchModeLabel(mode)!}]
        : []),
    ];
  });

  const actions = computed<ActionItems>(() => [
    {
      label: step.enabled ? t('Disable step') : t('Enable step'),
      icon: step.enabled ? 'circle-dashed' : 'circle',
      disabled: !canToggle,
      onClick: () => emit('toggleEnabled'),
    },
    {
      label: t('Delete step'),
      icon: 'trash',
      variant: 'danger',
      onClick: () => emit('remove'),
    },
  ]);

  /** What the run band says once the step's done, or while it waits its turn. */
  const runLabel = computed(() => {
    switch (run?.status) {
      case 'pending':
        return t('Waiting to run');
      case 'completed':
        return t('Completed');
      case 'failed':
        return errorSummary.value;
    }

    return '';
  });

  const errorSummary = computed(() =>
    run?.errorCount
      ? t(
          '{count, plural, =1 {# item} other {# items}} couldn’t be imported in the last run.',
          {count: run.errorCount}
        )
      : t('The last run stopped before this step finished.')
  );
</script>

<template>
  <li
    class="step-card"
    :class="{
      'step-card--over': over,
      'opacity-60': !step.enabled,
      [`step-card--${run?.status}`]: run,
    }"
    :aria-labelledby="labelId"
  >
    <div class="step-card__card">
      <aside class="step-card__rail">
        <div class="flex items-center justify-between gap-sm">
          <span class="step-card__number" aria-hidden="true">{{ number }}</span>

          <div class="flex items-center gap-sm">
            <ActionMenu
              v-if="editable"
              :label="t('Actions for {item}', {item: name})"
              :actions="actions"
            />
            <slot name="handle"></slot>
          </div>
        </div>

        <div class="grid gap-xs">
          <div :id="labelId" class="flex items-center">
            <div class="sr-only">{{ `${t('Step {number}', {number})}: ` }}</div>
            <div class="text-xl/snug font-bold">{{ typeLabel }}</div>
            <craft-badge v-if="!step.enabled" size="small" no-prefix>{{
              t('Disabled')
            }}</craft-badge>
          </div>
          <!-- a long path wraps after a slash, and only breaks a segment that can't fit -->
          <code v-if="step.source" class="step-card__source text-quiet"
            ><template v-for="(part, i) in sourceParts" :key="i"
              ><wbr v-if="i > 0" />{{ part }}</template
            ></code
          >
        </div>

        <p v-if="invalid" :id="errorId" class="error m-0">
          {{
            t('The step “{step}” is invalid. Edit it to fix the problem.', {
              step: description,
            })
          }}
        </p>

        <dl v-if="settingsRows.length" class="step-card__settings">
          <template v-for="setting in settingsRows" :key="setting.label">
            <dt class="text-quiet">{{ setting.label }}</dt>
            <dd class="m-0">{{ setting.value }}</dd>
          </template>
        </dl>

        <div class="mt-auto pt-md">
          <craft-button
            ref="settingsButton"
            size="small"
            flush
            variant="plain"
            :icon="editable ? 'pencil' : 'eye'"
            .loading="opening === 'settings'"
            :aria-describedby="invalid ? errorId : undefined"
            :aria-label="
              editable
                ? t('Edit {type}', {type: name})
                : t('View {type}', {type: name})
            "
            @click="emit('edit', settingsButton)"
          >
            {{ editable ? t('Edit settings') : t('View settings') }}
          </craft-button>
        </div>
      </aside>

      <section class="step-card__mapping">
        <StepMappingSummary
          :step="step"
          :labels="summary?.destinationLabels ?? {}"
        >
          <template #header="{count}">
            <div class="flex items-center justify-between gap-sm">
              <h3 class="m-0 text-base">{{ t('Mapping') }}</h3>
              <craft-button
                v-if="count"
                ref="mappingButton"
                size="small"
                variant="plain"
                :icon="editable ? 'pencil' : 'eye'"
                .loading="opening === 'mapping'"
                :aria-label="
                  editable
                    ? t('Edit mapping for {type}', {type: name})
                    : t('View mapping for {type}', {type: name})
                "
                @click="emit('editMapping', mappingButton)"
              >
                {{ editable ? t('Edit mapping') : t('View mapping') }}
              </craft-button>
            </div>
          </template>
          <!-- like a nested element field's add button -->
          <template v-if="editable" #empty>
            <div>
              <craft-button
                ref="mappingButton"
                type="button"
                variant="dashed"
                icon="plus"
                .loading="opening === 'mapping'"
                :aria-label="t('Add mapping for {type}', {type: name})"
                @click="emit('editMapping', mappingButton)"
              >
                {{ t('Add mapping') }}
              </craft-button>
            </div>
          </template>
        </StepMappingSummary>
      </section>
    </div>

    <section
      v-if="run"
      :id="stepRunId(step.uid)"
      class="step-card__run"
      :aria-label="t('The last run')"
    >
      <div class="step-card__run-status">
        <RunIndicator :status="run.status" :progress="run.progress" />
        <template v-if="run.status === 'running'">
          <craft-progress-bar
            class="step-card__run-progress"
            :progress="Math.max(run.progress, 0)"
            :pending="run.progress < 0"
            :label="t('Progress')"
            smooth
          ></craft-progress-bar>
          <span v-if="run.progress >= 0" class="text-quiet">{{
            t('{percent, number}% done', {percent: run.progress})
          }}</span>
        </template>
        <span v-else class="font-semibold">{{ runLabel }}</span>
        <craft-button
          v-if="run.errors?.length"
          class="step-card__run-details"
          type="button"
          size="small"
          inherit
          :aria-label="t('Show details of the errors in {type}', {type: name})"
          @click="showingErrors = true"
        >
          {{ t('Show details') }}
        </craft-button>
      </div>
      <RunErrorsModal
        v-if="run.errors?.length"
        :is-active="showingErrors"
        :title="name"
        :errors="run.errors"
        :error-count="run.errorCount"
        @close="showingErrors = false"
      />
    </section>
  </li>
</template>

<style scoped>
  .step-card {
    --_frame: var(--c-spacing-xs);
    border-radius: calc(var(--c-radius-lg) + var(--_frame));
  }

  /* the last run's status and errors sit in a tinted frame around the card */
  .step-card:is(.step-card--pending, .step-card--running) {
    padding: var(--_frame);
    background-color: var(--c-color-neutral-fill-quiet);
  }

  .step-card--completed {
    padding: var(--_frame);
    background-color: var(--c-color-success-fill-quiet);
    color: var(--c-color-success-on-quiet);
  }

  .step-card--failed {
    padding: var(--_frame);
    background-color: var(--c-color-danger-fill-quiet);
    color: var(--c-color-danger-on-quiet);
  }

  .step-card__card {
    display: grid;
    grid-template-columns: minmax(14rem, 18rem) minmax(0, 1fr);
    overflow: hidden;
    background-color: var(--c-surface-default);
    border: 1px solid var(--c-color-neutral-border-quiet);
    border-radius: var(--c-radius-lg);
    color: var(--c-text-default);
  }

  .step-card--over .step-card__card {
    border-color: var(--c-color-accent-border-normal);
  }

  .step-card__run {
    display: grid;
    gap: var(--c-spacing-sm);
    padding-block: var(--c-spacing-sm);
    padding-inline: var(--c-spacing-md);
  }

  .step-card__run-status {
    display: flex;
    align-items: center;
    gap: var(--c-spacing-sm);
  }

  .step-card__run-progress {
    flex: 1;
    max-inline-size: 24rem;
    --c-progress-bar-fill-color: var(--c-color-success-fill-loud);
  }

  .step-card__run-details {
    margin-inline-start: auto;
  }

  .step-card__rail {
    display: flex;
    flex-direction: column;
    gap: var(--c-spacing-md);
    padding: var(--c-spacing-lg);
    background-color: var(--c-surface-sunken);
    border-inline-end: 1px solid var(--c-color-neutral-border-quiet);
  }

  .step-card__number {
    display: grid;
    place-items: center;
    /* A lil optical adjustment */
    margin-inline-start: -0.25em;
    inline-size: calc(var(--c-spacing) * 6);
    block-size: calc(var(--c-spacing) * 6);
    border-radius: var(--c-radius-full);
    border: 1px solid var(--c-color-border-quiet);
    color: var(--c-color-on-quiet);
    font-size: var(--c-text-sm);
    font-weight: var(--font-weight-semibold);
  }

  .step-card__source {
    overflow-wrap: anywhere;
  }

  .step-card__settings {
    display: grid;
    grid-template-columns: max-content minmax(0, 1fr);
    gap: var(--c-spacing-sm) var(--c-spacing-xl);
    margin-block: var(--c-spacing-md);
  }

  .step-card__mapping {
    padding: var(--c-spacing-lg);
    min-width: 0;
  }

  @media (width < var(--breakpoint-md)) {
    .step-card__card {
      grid-template-columns: minmax(0, 1fr);
    }

    .step-card__rail {
      border-inline-end: 0;
      border-block-end: 1px solid var(--c-color-neutral-border-quiet);
    }
  }
</style>
