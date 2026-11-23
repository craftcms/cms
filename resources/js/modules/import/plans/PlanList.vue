<script setup lang="ts">
  /**
   * Import plans as a list, each with its steps beneath it when shown. Each plan is its own
   * article, named by its heading, and its steps an ordered list, so nothing is a table within
   * a table.
   */
  import '@craftcms/ui/components/badge/badge';
  import '@craftcms/ui/components/button/button';
  import '@craftcms/ui/components/checkbox/checkbox';
  import '@craftcms/ui/components/icon/icon';
  import '@craftcms/ui/components/tooltip/tooltip';
  import {t} from '@craftcms/ui';
  import {computed, ref, watch} from 'vue';
  import ActionMenu from '@/common/components/ActionMenu.vue';
  import CpLink from '@/common/components/CpLink.vue';
  import {openSlideout} from '@/common/slideouts';
  import type {ActionItems} from '@/common/types';
  import AdminTableBulkActionsBar from '@/modules/admin-table/components/AdminTableBulkActionsBar.vue';
  import type {BulkAction} from '@/modules/elements/types/actions';
  import {matchModeLabel} from '@/modules/import/mapping/matching';
  import RunButton from '@/modules/import/run/RunButton.vue';
  import RunIndicator from '@/modules/import/run/RunIndicator.vue';
  import {runCounts} from '@/modules/import/run/run-format';
  import StepNumber from '@/modules/import/steps/StepNumber.vue';
  import {stepDestination} from '@/modules/import/steps/step-settings';
  import type {RunState, RunStatus} from '@/modules/import/run/run-state';
  import type {PlanBulkAction} from './types';

  type ImportPlan = CraftCms.Cms.Import.Data.ImportPlanIndexData;
  type ImportStep = ImportPlan['steps'][number];

  const {
    plans,
    label,
    canTrigger = false,
    bulkActions = [],
    actions,
    planUrl,
    stepUrl,
    isRunning,
    canEditMapping = () => false,
    openingMapping = null,
  } = defineProps<{
    plans: ImportPlan[];
    /** Names the list for screen readers. */
    label: string;
    canTrigger?: boolean;
    /** What can be done to the selected plans; with none, plans can't be selected. */
    bulkActions?: PlanBulkAction[];
    /** Each plan's action menu. */
    actions: (plan: ImportPlan) => ActionItems;
    /** Each plan's edit page, if it has one. */
    planUrl: (plan: ImportPlan) => string | null;
    /** Each step's edit page, if it has one. */
    stepUrl: (plan: ImportPlan, step: ImportStep) => string | null;
    /** Whether the plan is running, or about to. */
    isRunning: (plan: ImportPlan) => boolean;
    /** Whether a step's mapping can be edited from the list. */
    canEditMapping?: (plan: ImportPlan, step: ImportStep) => boolean;
    /** The uid of the step whose mapping is opening. */
    openingMapping?: string | null;
  }>();

  const emit = defineEmits<{
    run: [plan: ImportPlan];
    editMapping: [plan: ImportPlan, step: ImportStep, opener: HTMLElement];
  }>();

  /** The plans showing their steps, by handle. */
  const expanded = ref(new Set<string>());
  const withSteps = computed(() => plans.filter((plan) => plan.stepCount > 0));
  const allExpanded = computed(
    () =>
      withSteps.value.length > 0 &&
      withSteps.value.every((plan) => expanded.value.has(plan.handle))
  );

  function toggleSteps(plan: ImportPlan): void {
    const next = new Set(expanded.value);

    if (!next.delete(plan.handle)) {
      next.add(plan.handle);
    }

    expanded.value = next;
  }

  function toggleAll(): void {
    expanded.value = allExpanded.value
      ? new Set()
      : new Set(withSteps.value.map((plan) => plan.handle));
  }

  /** The selected plans, by handle. */
  const selected = ref(new Set<string>());
  const selectedPlans = computed(() =>
    plans.filter((plan) => selected.value.has(plan.handle))
  );

  function select(plan: ImportPlan, checked: boolean): void {
    const next = new Set(selected.value);

    if (checked) {
      next.add(plan.handle);
    } else {
      next.delete(plan.handle);
    }

    selected.value = next;
  }

  function selectAll(checked: boolean): void {
    selected.value = checked
      ? new Set(plans.map((plan) => plan.handle))
      : new Set();
  }

  const selectable = computed(() => bulkActions.length > 0);

  const bulkBarActions = computed<BulkAction[]>(() =>
    bulkActions
      .map((action) => ({
        action,
        plans: selectedPlans.value.filter(
          (plan) => action.appliesTo?.(plan) ?? true
        ),
      }))
      .filter(({plans}) => plans.length > 0)
      .map(({action, plans}) => ({
        key: action.key,
        label: action.label,
        destructive: action.destructive,
        variant: action.destructive ? 'danger' : undefined,
        onClick: () => {
          selected.value = new Set();
          action.perform(plans);
        },
      }))
  );

  // a plan filtered out of the list can't stay selected, unseen
  watch(
    () => plans,
    (plans) => {
      const handles = new Set(plans.map((plan) => plan.handle));
      const kept = [...selected.value].filter((handle) => handles.has(handle));

      if (kept.length !== selected.value.size) {
        selected.value = new Set(kept);
      }
    }
  );

  function headingId(plan: ImportPlan): string {
    return `import-plan-${plan.handle}`;
  }

  function stepCountLabel(plan: ImportPlan): string {
    return t('{count, plural, =0 {No steps} =1 {# step} other {# steps}}', {
      count: plan.stepCount,
    });
  }

  /** How far a running plan has got, or -1 while that isn't known yet. */
  function runProgress(plan: ImportPlan): number {
    return plan.running && plan.progress !== null ? plan.progress : -1;
  }

  /** A run that's only just been asked for is pending, whatever its last run came to. */
  function runStatus(plan: ImportPlan): RunStatus {
    return plan.running
      ? ((plan.run?.status as RunStatus) ?? 'pending')
      : 'pending';
  }

  /** Whether the plan's run is under way, rather than just finished. */
  function inProgress(plan: ImportPlan): boolean {
    return isRunning(plan) && ['pending', 'running'].includes(runStatus(plan));
  }

  /** What the run under way has got to, as its line says. */
  function progressLabel(plan: ImportPlan): string {
    if (runStatus(plan) === 'pending') {
      return t('Waiting to run');
    }

    const progress = runProgress(plan);

    return progress < 0
      ? t('Running')
      : t('Running: {percent, number}% done', {percent: progress});
  }

  /** The run under way, once the server has started it, rather than the one before it. */
  function currentRun(plan: ImportPlan): RunState | null {
    return plan.running ? lastRun(plan) : null;
  }

  /** The plan's last run, once it has one. */
  function lastRun(plan: ImportPlan): RunState | null {
    return (plan.run as RunState | null) ?? null;
  }

  /** How many of the last run's items failed. */
  function lastRunFailures(run: RunState): number {
    return runCounts(run).failed;
  }

  /** What the step imports into, e.g. “Guides › Guide”: its settings other than the site. */
  function destination(step: ImportStep): string {
    return stepDestination(step.settings);
  }

  function site(step: ImportStep): string {
    return step.settings.find((setting) => setting.key === 'site')?.value ?? '';
  }

  function fileName(source: string | null): string {
    return source?.split('/').pop() ?? '';
  }

  function fieldCount(step: ImportStep): string {
    return t('{count, plural, =1 {# field} other {# fields}}', {
      count: step.mappedFields,
    });
  }

  /** How a step is getting on in the run under way; once the run's over, its steps show nothing. */
  function stepRun(
    plan: ImportPlan,
    step: ImportStep
  ): {status: RunStatus; progress: number} | null {
    // not until the server has the new run, either, or they'd show how the last one went
    const run =
      step.uid && plan.running && inProgress(plan)
        ? plan.run?.steps[step.uid]
        : undefined;

    return run
      ? {status: run.status as RunStatus, progress: run.progress}
      : null;
  }

  /** Double-clicking a row opens its edit page in a slideout, as an element index does. */
  function openFromRow(event: MouseEvent, url: string | null): void {
    const target = event.target instanceof Element ? event.target : null;

    if (!url || target?.closest('a, button, input, [role="button"]')) {
      return;
    }

    event.stopPropagation();
    void openSlideout(url);
  }
</script>

<template>
  <div class="plan-list" :class="{'plan-list--selectable': selectable}">
    <div class="plan-list__toolbar">
      <span class="plan-list__toggle">
        <craft-button
          v-if="withSteps.length"
          id="plan-list-toggle-all"
          type="button"
          variant="plain"
          size="small"
          :icon="allExpanded ? 'arrows-to-line' : 'arrows-from-line'"
          :aria-label="
            allExpanded ? t('Collapse all plans') : t('Expand all plans')
          "
          @click="toggleAll"
        ></craft-button>
        <craft-tooltip v-if="withSteps.length" for="plan-list-toggle-all">{{
          allExpanded ? t('Collapse all') : t('Expand all')
        }}</craft-tooltip>
      </span>
      <craft-checkbox
        v-if="selectable"
        class="plan-list__select-all"
        label-sr-only
        .checked="plans.length > 0 && selected.size === plans.length"
        .indeterminate="selected.size > 0 && selected.size < plans.length"
        @change="selectAll(($event.target as HTMLInputElement).checked)"
        ><label slot="label">{{ t('Select all') }}</label></craft-checkbox
      >
      <div v-if="$slots['toolbar-filters']" class="plan-list__toolbar-filters">
        <slot name="toolbar-filters"></slot>
      </div>
      <div v-if="$slots['toolbar-actions']" class="plan-list__toolbar-actions">
        <slot name="toolbar-actions"></slot>
      </div>
    </div>

    <ul class="plan-list__plans" :aria-label="label">
      <li
        v-for="plan in plans"
        :key="plan.handle"
        class="plan-list__plan"
        :class="{'plan-list__plan--selected': selected.has(plan.handle)}"
        @dblclick="openFromRow($event, planUrl(plan))"
      >
        <article :aria-labelledby="headingId(plan)">
          <div class="plan-list__header">
            <span class="plan-list__toggle">
              <craft-button
                v-if="plan.stepCount"
                type="button"
                variant="plain"
                size="small"
                icon=""
                :aria-expanded="String(expanded.has(plan.handle))"
                @click="toggleSteps(plan)"
              >
                <craft-icon
                  :name="
                    expanded.has(plan.handle) ? 'chevron-down' : 'chevron-right'
                  "
                  :label="
                    expanded.has(plan.handle)
                      ? t('Hide {name}’s steps', {name: plan.name})
                      : t('Show {name}’s steps', {name: plan.name})
                  "
                ></craft-icon>
              </craft-button>
            </span>
            <craft-checkbox
              v-if="selectable"
              class="plan-list__select"
              label-sr-only
              .checked="selected.has(plan.handle)"
              @change="
                select(plan, ($event.target as HTMLInputElement).checked)
              "
              ><label slot="label">{{
                t('Select {label}', {label: plan.name})
              }}</label></craft-checkbox
            >
            <div class="plan-list__title">
              <h3 :id="headingId(plan)" class="plan-list__name">
                <CpLink v-if="planUrl(plan)" :href="planUrl(plan)!">{{
                  plan.name
                }}</CpLink>
                <template v-else>{{ plan.name }}</template>
                <craft-badge
                  v-if="!plan.editable"
                  class="plan-list__code"
                  size="small"
                  no-prefix
                  >{{ t('Code') }}</craft-badge
                >
              </h3>
              <p
                v-if="plan.description"
                class="plan-list__description text-quiet"
              >
                {{ plan.description }}
              </p>
              <p
                v-if="inProgress(plan)"
                class="plan-list__last-run text-quiet"
                aria-live="polite"
              >
                <span class="plan-list__last-run-status">
                  <RunIndicator
                    :status="runStatus(plan)"
                    :progress="runProgress(plan)"
                  />
                </span>
                <span>{{ progressLabel(plan) }}</span>
                <template v-if="currentRun(plan)">
                  <span class="plan-list__last-run-bullet" aria-hidden="true"
                    >•</span
                  >
                  <CpLink :href="currentRun(plan)!.url">{{
                    t('View run')
                  }}</CpLink>
                </template>
              </p>
              <p
                v-else-if="lastRun(plan)"
                class="plan-list__last-run text-quiet"
              >
                <span class="plan-list__last-run-status">
                  <RunIndicator
                    :status="lastRun(plan)!.status"
                    :progress="lastRun(plan)!.progress"
                  />
                </span>
                <span :title="lastRun(plan)!.started.title">{{
                  t('Last run {date}', {date: lastRun(plan)!.started.label})
                }}</span>
                <template v-if="lastRunFailures(lastRun(plan)!)">
                  <span class="plan-list__last-run-bullet" aria-hidden="true"
                    >•</span
                  >
                  <span class="plan-list__last-run-failed">{{
                    t('{count, number} Failed', {
                      count: lastRunFailures(lastRun(plan)!),
                    })
                  }}</span>
                </template>
                <span class="plan-list__last-run-bullet" aria-hidden="true"
                  >•</span
                >
                <CpLink :href="lastRun(plan)!.url">{{ t('View run') }}</CpLink>
              </p>
            </div>
            <span class="plan-list__meta">
              <craft-copy-attribute
                class="plan-list__handle"
                :value="plan.handle"
                >{{ plan.handle }}</craft-copy-attribute
              >
              <span class="plan-list__step-count">{{
                stepCountLabel(plan)
              }}</span>
            </span>
            <span class="plan-list__run">
              <RunButton
                v-if="canTrigger"
                :name="plan.name"
                :running="isRunning(plan)"
                :status="runStatus(plan)"
                :progress="runProgress(plan)"
                @run="emit('run', plan)"
              />
            </span>
            <span class="plan-list__actions">
              <ActionMenu
                v-if="actions(plan).length"
                :label="t('Actions for {item}', {item: plan.name})"
                :actions="actions(plan)"
              />
            </span>
          </div>

          <template v-if="expanded.has(plan.handle) && plan.steps.length">
            <div class="plan-list__columns" aria-hidden="true">
              <span></span>
              <span>{{ t('Summary') }}</span>
              <span>{{ t('Site') }}</span>
              <span>{{ t('Source') }}</span>
              <span>{{ t('Mapping') }}</span>
              <span>{{ t('If matched') }}</span>
              <span></span>
            </div>
            <ol
              class="plan-list__steps"
              :aria-label="t('Steps of {name}', {name: plan.name})"
            >
              <li
                v-for="(step, stepIndex) in plan.steps"
                :key="step.uid ?? stepIndex"
                class="plan-list__step"
                :class="{'plan-list__step--disabled': !step.enabled}"
                @dblclick="openFromRow($event, stepUrl(plan, step))"
              >
                <StepNumber
                  class="plan-list__step-number"
                  size="small"
                  :number="stepIndex + 1"
                />
                <div class="plan-list__step-name">
                  <CpLink
                    v-if="stepUrl(plan, step)"
                    :href="stepUrl(plan, step)!"
                    >{{ step.label }}</CpLink
                  >
                  <template v-else>{{ step.label }}</template>
                  <span v-if="destination(step)" class="text-quiet"
                    >→ {{ destination(step) }}</span
                  >
                  <craft-badge v-if="!step.enabled" size="small" no-prefix>{{
                    t('Disabled')
                  }}</craft-badge>
                </div>
                <span class="plan-list__step-detail" :data-label="t('Site')">{{
                  site(step)
                }}</span>
                <code
                  class="plan-list__step-source"
                  :data-label="t('Source')"
                  :title="step.source ?? undefined"
                  >{{ fileName(step.source) }}</code
                >
                <span
                  class="plan-list__step-detail plan-list__step-mapping"
                  :data-label="t('Mapping')"
                  >{{ fieldCount(step)
                  }}<template v-if="canEditMapping(plan, step)"
                    ><craft-button
                      :id="`plan-list-mapping-${step.uid}`"
                      type="button"
                      size="small"
                      variant="plain"
                      icon="pencil"
                      .loading="openingMapping === step.uid"
                      :aria-label="
                        t('Edit mapping for {step}', {step: step.label})
                      "
                      @click="
                        emit(
                          'editMapping',
                          plan,
                          step,
                          $event.currentTarget as HTMLElement
                        )
                      "
                    ></craft-button
                    ><craft-tooltip :for="`plan-list-mapping-${step.uid}`">{{
                      t('Edit')
                    }}</craft-tooltip></template
                  ></span
                >
                <span
                  class="plan-list__step-detail"
                  :data-label="t('If matched')"
                  >{{
                    step.matchMode ? matchModeLabel(step.matchMode) : ''
                  }}</span
                >
                <span class="plan-list__step-status">
                  <RunIndicator
                    v-if="stepRun(plan, step)"
                    :status="stepRun(plan, step)!.status"
                    :progress="stepRun(plan, step)!.progress"
                  />
                </span>
              </li>
            </ol>
          </template>
        </article>
      </li>
    </ul>

    <div v-if="selected.size && selectable" class="plan-list__bulk">
      <AdminTableBulkActionsBar
        :selected-ids="[...selected]"
        :actions="bulkBarActions"
        @clear="selected = new Set()"
      />
    </div>
  </div>
</template>

<style scoped>
  /*
   * The toggle, checkbox and run columns are one width apiece in the toolbar, each plan
   * and its steps, so they line up: each step's status under its plan's run button.
   */
  .plan-list {
    /* the container's padding, so the checkboxes start where its content does */
    --_edge: var(--cp-container-padding, var(--c-spacing-md));
    /* the expand toggles hang off to the left of them, in that padding */
    --_hang: max(var(--_edge), var(--_control) + var(--_toggle-gap));
    --_toggle-gap: calc(var(--_select-gap) / 2);
    /* a step's number column spans the checkbox and its gap, so the step's name lines up with its plan's */
    --_number-col: calc(var(--_checkbox) + var(--_select-gap) - var(--_gap));
    /* a small step number's */
    --_number-size: 1.25rem;
    --_gap: var(--c-spacing-md);
    /* past the checkboxes, so the plan names and the toolbar's filters stand clear of them */
    --_select-gap: var(--c-spacing-lg);
    --_control: var(--c-size-control-sm);
    --_checkbox: var(--c-checkbox-size);
    /* room either side of the run column */
    --_run-inset: var(--c-spacing-lg);
    container-type: inline-size;
    display: grid;
    gap: var(--c-spacing-sm);
  }

  .plan-list__toggle,
  .plan-list__run,
  .plan-list__actions {
    display: inline-grid;
    flex: none;
    place-items: center;
    inline-size: var(--_control);
  }

  .plan-list__toggle {
    box-sizing: border-box;
    justify-items: end;
    inline-size: var(--_hang);
    padding-inline-end: var(--_toggle-gap);
  }

  .plan-list__plans {
    margin: 0;
    padding: 0;
    list-style: none;
    border-block: 1px solid var(--c-color-neutral-border-quiet);
  }

  .plan-list__plan + .plan-list__plan {
    border-block-start: 1px solid var(--c-color-neutral-border-quiet);
  }

  .plan-list__plan--selected {
    background-color: var(--c-color-accent-fill-quiet);
  }

  /*
   * Every control shares the plan name's row, centred on it, with the description below
   * the name. Columns are sized by what's in them, so a list without checkboxes has no
   * room left for them; each item brings its own gap for the same
   * reason.
   */
  .plan-list__toolbar,
  .plan-list__header {
    display: grid;
    grid-template-columns:
      [toggle] auto [select] auto [title] minmax(0, 1fr)
      [handle] auto [count] auto [run] auto [actions] auto [end];
    align-items: center;
    padding-inline: 0 var(--_edge);
  }

  .plan-list__header {
    padding-block: var(--c-spacing-lg);
  }

  /* the toolbar's checkbox lines up with the plans' */
  .plan-list__select-all {
    grid-column: select;
  }

  .plan-list__toolbar-filters {
    grid-column: title;
  }

  .plan-list__toolbar-actions {
    grid-column: handle / end;
    justify-self: end;
    display: flex;
    gap: var(--c-spacing-sm);
  }

  .plan-list__toolbar > *,
  .plan-list__header > *,
  .plan-list__meta > *,
  .plan-list__name {
    grid-row: 1;
  }

  .plan-list__toggle {
    grid-column: toggle;
  }

  .plan-list__select {
    grid-column: select;
  }

  .plan-list__name,
  .plan-list__handle {
    margin-inline-end: var(--_gap);
  }

  .plan-list__select,
  .plan-list__select-all {
    margin-inline-end: var(--_select-gap);
  }

  .plan-list__title,
  .plan-list__meta {
    display: contents;
  }

  .plan-list__name {
    grid-column: title;
    display: flex;
    align-items: center;
    gap: var(--c-spacing-md);
    margin-block: 0;
    font-size: var(--c-text-lg);
  }

  .plan-list__code {
    flex: none;
  }

  .plan-list__description {
    grid-row: 2;
    grid-column: title;
    margin-block: var(--c-spacing-2xs) 0;
    margin-inline: 0 var(--_gap);
  }

  /* the last run, under the description */
  .plan-list__last-run {
    grid-row: 3;
    grid-column: title;
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: var(--c-spacing-xs) var(--c-spacing-sm);
    margin-block: var(--c-spacing-sm) 0;
    margin-inline: 0 var(--_gap);
    font-size: var(--c-text-sm);
  }

  /* as small as the line's text */
  .plan-list__last-run-status {
    display: inline-flex;
    --c-size-control-sm: 1rem;
    --c-run-indicator-stroke-width: 1.5px;
  }

  /* a part of its own, so the gap either side of it is the same */
  .plan-list__last-run-bullet {
    color: var(--c-text-quiet);
  }

  .plan-list__last-run-failed {
    color: var(--c-color-danger-on-quiet);
    font-weight: var(--font-weight-semibold);
  }

  .plan-list__handle {
    grid-column: handle;
  }

  .plan-list__step-count {
    grid-column: count;
  }

  .plan-list__run {
    grid-column: run;
    margin-inline: var(--_run-inset) calc(var(--_gap) + var(--_run-inset) / 2);
  }

  .plan-list__actions {
    grid-column: actions;
  }

  .plan-list__step-count {
    margin-inline-end: calc(var(--_gap) + var(--c-spacing-md));
    inline-size: 4.5rem;
    color: var(--c-color-on-quiet);
    font-size: var(--c-text-sm);
    text-align: end;
  }

  /*
   * Each step's number starts under its plan's name, and its status ends under the run
   * button: past it are the action menu's column and the edge.
   */
  .plan-list__columns,
  .plan-list__step {
    display: grid;
    grid-template-columns:
      var(--_number-col) minmax(0, 1fr) minmax(0, 10rem) minmax(0, 13rem)
      minmax(0, 9rem) minmax(0, 10rem) var(--_control);
    align-items: center;
    column-gap: var(--_gap);
    padding-inline: var(--_hang)
      calc(var(--_edge) + var(--_gap) + var(--_control) + var(--_run-inset) / 2);
    background-color: color-mix(
      in srgb,
      var(--c-surface-sunken) 50%,
      var(--c-surface-default)
    );
  }

  /* as a table's heading row */
  .plan-list__columns {
    min-block-size: var(--c-size-header);
    padding-block: var(--c-spacing-sm);
    border-block: 1px solid var(--c-color-neutral-border-quiet);
    color: var(--c-color-on-quiet);
    font-size: var(--c-text-sm);
    font-weight: var(--font-weight-bold);
  }

  .plan-list__steps {
    margin: 0;
    padding: 0;
    list-style: none;
  }

  /* as a spacious table's row */
  .plan-list__step {
    min-block-size: calc(50rem / 16);
    padding-block: var(--c-spacing-lg);
  }

  .plan-list__step + .plan-list__step {
    border-block-start: 1px solid var(--c-color-neutral-border-quiet);
  }

  .plan-list__step--disabled {
    opacity: 0.6;
  }

  /* centred under the plan's checkbox, which is narrower */
  /* centred under the plan's checkbox */
  .plan-list__step-number {
    justify-self: start;
    margin-inline-start: calc((var(--_checkbox) - var(--_number-size)) / 2);
  }

  .plan-list__step-name {
    display: flex;
    flex-wrap: wrap;
    align-items: baseline;
    gap: var(--c-spacing-xs) var(--c-spacing-sm);
    font-weight: var(--font-weight-semibold);
  }

  .plan-list__step-name .text-quiet {
    font-weight: normal;
  }

  .plan-list__step-detail {
    color: var(--c-color-on-quiet);
  }

  .plan-list__step-mapping {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: var(--c-spacing-sm);
  }

  .plan-list__step-source {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }

  /* stays in view at the bottom of the window while plans are selected, as an admin table's footer does */
  .plan-list__bulk {
    position: sticky;
    inset-block-end: 0;
    z-index: 1;
    display: flex;
    align-items: center;
    min-block-size: var(--cp-footer-height);
    padding-inline: var(--_edge);
    border-block-start: 1px solid var(--c-color-neutral-border-quiet);
    background-color: var(--c-surface-default);
  }

  .plan-list__step-status {
    display: grid;
    place-items: center;
  }

  /*
   * Narrow, a plan's handle and step count drop below its title, and each step's details
   * stack under its name, labelled, as the column headings are left out.
   */
  @container (inline-size < 48rem) {
    /* what's worked out from these is redeclared, or it keeps the wider values */
    .plan-list > * {
      --_gap: var(--c-spacing-sm);
      --_select-gap: var(--c-spacing-md);
      --_run-inset: var(--c-spacing-sm);
      --_toggle-gap: calc(var(--_select-gap) / 2);
      --_hang: max(var(--_edge), var(--_control) + var(--_toggle-gap));
      --_number-col: calc(var(--_checkbox) + var(--_select-gap) - var(--_gap));
    }

    /* the handle and step count drop below the description, from the title's column */
    .plan-list__meta {
      display: flex;
      flex-wrap: wrap;
      grid-row: 4;
      grid-column: title / end;
      align-items: center;
      gap: var(--c-spacing-md);
      margin-block-start: var(--c-spacing-md);
    }

    .plan-list__handle {
      margin-inline-end: 0;
    }

    .plan-list__step-count {
      margin-inline-end: 0;
      inline-size: auto;
    }

    .plan-list__columns {
      display: none;
    }

    .plan-list__step {
      grid-template-columns: var(--_number-col) minmax(0, 1fr) var(--_control);
      row-gap: var(--c-spacing-xs);
      padding-inline-end: calc(
        var(--_edge) + var(--_gap) + var(--_control) + var(--_run-inset) / 2
      );
    }

    .plan-list__step > * {
      grid-column: 2;
    }

    .plan-list__step-number {
      grid-column: 1;
      grid-row: 1;
    }

    .plan-list__step-status {
      grid-column: 3;
      grid-row: 1;
    }

    .plan-list__step-detail:empty {
      display: none;
    }

    .plan-list__step-detail::before,
    .plan-list__step-source::before {
      content: attr(data-label) ': ';
      font-family: var(--c-font-body);
      font-weight: var(--font-weight-semibold);
    }
  }
</style>
