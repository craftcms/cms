<script setup lang="ts">
  import '@craftcms/ui/components/button/button';
  import {actionClient, ButtonVariant, t} from '@craftcms/ui';
  import {computed, ref, watch} from 'vue';
  import {router, usePoll} from '@inertiajs/vue3';
  import CpButtonLink from '@/common/components/CpButtonLink.vue';
  import CpContainer from '@/common/components/CpContainer.vue';
  import LayoutSlot from '@/common/components/LayoutSlot.vue';
  import {useLocalStorage} from '@/common/composables/useStorage';
  import type {ActionItems} from '@/common/types';
  import {showError, showSuccess} from '@/modules/messages/messages';
  import PlanList from '@/modules/import/plans/PlanList.vue';
  import PlanSortMenu, {
    type PlanSortDirection,
    type PlanSortField,
  } from '@/modules/import/plans/PlanSortMenu.vue';
  import type {PlanBulkAction} from '@/modules/import/plans/types';
  import {
    create,
    destroy,
    duplicate,
    edit,
    editStep,
    editStepMapping,
    run,
    showStep,
    storeStep,
  } from '@actions/Import/ImportPlansController';
  import type {ImportStep as StepData} from '@/modules/import/mapping/types';
  import {openStepMapping} from '@/modules/import/steps/step-mapping';

  type ImportPlan = CraftCms.Cms.Import.Data.ImportPlanIndexData;
  type ImportStep = ImportPlan['steps'][number];

  const props = defineProps<{
    canSave: boolean;
    canDelete: boolean;
    canTrigger: boolean;
    editableImportPlans: Array<ImportPlan>;
    nonEditableImportPlans: Array<ImportPlan>;
  }>();

  function planUrl(plan: ImportPlan): string {
    return edit({handle: plan.handle}).url;
  }

  function stepUrl(plan: ImportPlan, step: ImportStep): string | null {
    return plan.editable && step.uid
      ? editStep({handle: plan.handle, uid: step.uid}).url
      : null;
  }

  /** What a run is posted with: a saved plan by its uid, one registered in code by its handle. */
  function runBody(plan: ImportPlan): Record<string, string | null> {
    return plan.editable ? {uid: plan.uid} : {handle: plan.handle};
  }

  /** Plans whose run request is still in flight, before the server reports them running. */
  const starting = ref(new Set<string>());

  function isRunning(plan: ImportPlan): boolean {
    return plan.running || starting.value.has(plan.handle);
  }

  function markStarting(plans: ImportPlan[], value: boolean): void {
    const next = new Set(starting.value);
    plans.forEach((plan) =>
      value ? next.add(plan.handle) : next.delete(plan.handle)
    );
    starting.value = next;
  }

  /** Starts a run, then follows it to its page, unless it's to run in the background. */
  function startRun(plan: ImportPlan, background = false): void {
    if (
      isRunning(plan) ||
      !window.confirm(
        t('Are you sure you want to run “{name}”?', {name: plan.name})
      )
    ) {
      return;
    }

    router.post(
      run().url,
      {...runBody(plan), background},
      {
        preserveScroll: true,
        onStart: () => markStarting([plan], true),
        onFinish: () => markStarting([plan], false),
      }
    );
  }

  /**
   * Runs the selected plans that aren't running yet, one request at a time, as each is
   * its own run, then reloads the plans once for their running state.
   */
  async function runSelected(selected: ImportPlan[]): Promise<void> {
    const plans = selected.filter((plan) => !isRunning(plan));

    if (
      !plans.length ||
      !window.confirm(
        t(
          'Are you sure you want to run {count, plural, =1 {“{name}”} other {these # import plans}}?',
          {count: plans.length, name: plans[0]!.name}
        )
      )
    ) {
      return;
    }

    markStarting(plans, true);
    let started = 0;

    for (const plan of plans) {
      try {
        await actionClient.post(run().url, runBody(plan));
        started++;
      } catch (error: any) {
        showError(
          t('Couldn’t run “{name}”: {message}', {
            name: plan.name,
            message:
              error?.response?.data?.message ?? t('A server error occurred.'),
          })
        );
      }
    }

    if (started) {
      showSuccess(
        t(
          '{count, plural, =1 {Started # import.} other {Started # imports.}}',
          {
            count: started,
          }
        )
      );
    }

    router.reload({
      only: ['editableImportPlans', 'nonEditableImportPlans'],
      onFinish: () => markStarting(plans, false),
    });
  }

  /** What can be done to the selected plans: run any, and duplicate or delete the editable ones. */
  const bulkActions = computed<PlanBulkAction[]>(() => {
    const actions: PlanBulkAction[] = [];

    if (props.canTrigger) {
      actions.push({
        key: 'run',
        label: t('Run'),
        perform: (plans) => void runSelected(plans),
      });
    }

    if (props.canSave) {
      actions.push({
        key: 'duplicate',
        label: t('Duplicate'),
        appliesTo: (plan) => plan.editable,
        perform: (plans) =>
          router.post(
            duplicate().url,
            {uids: plans.map((plan) => plan.uid)},
            {preserveScroll: true}
          ),
      });
    }

    if (props.canDelete) {
      actions.push({
        key: 'delete',
        label: t('Delete'),
        appliesTo: (plan) => plan.editable,
        destructive: true,
        perform: (plans) => {
          if (
            !window.confirm(
              plans.length === 1
                ? t('Are you sure you want to delete “{name}”?', {
                    name: plans[0]!.name,
                  })
                : t(
                    'Are you sure you want to delete these {count, number} import plans?',
                    {count: plans.length}
                  )
            )
          ) {
            return;
          }

          router.delete(destroy().url, {
            data: {uids: plans.map((plan) => plan.uid)},
            preserveScroll: true,
          });
        },
      });
    }

    return actions;
  });

  /** The uid of the step whose mapping is opening. */
  const openingMapping = ref<string | null>(null);

  function canEditMapping(plan: ImportPlan, step: ImportStep): boolean {
    return props.canSave && plan.editable && !!step.uid;
  }

  /**
   * Opens a step's mapping, as its plan's page does, and saves the step into its plan
   * once the mapping is applied, as there's no plan form here to hand it back to.
   */
  async function editMapping(
    plan: ImportPlan,
    summary: ImportStep,
    opener: HTMLElement
  ): Promise<void> {
    if (openingMapping.value !== null) {
      return;
    }

    openingMapping.value = summary.uid;

    try {
      const {data} = await actionClient.get<{step: StepData}>(showStep().url, {
        params: {planUid: plan.uid, uid: summary.uid},
      });
      const step = data.step;

      await openStepMapping(
        {
          step,
          editable: true,
          editUrl: summary.uid
            ? editStepMapping({handle: plan.handle, uid: summary.uid}).url
            : null,
          opener,
          apply: (values) => void saveMapping(plan, step, values),
        },
        t('Edit mapping')
      );
    } catch (error: any) {
      showError(
        error?.response?.data?.message ??
          (error instanceof Error && error.message
            ? error.message
            : t('Couldn’t open the mapping.'))
      );
    } finally {
      openingMapping.value = null;
    }
  }

  async function saveMapping(
    plan: ImportPlan,
    step: StepData,
    values: Partial<StepData['settings']>
  ): Promise<void> {
    // PHP sends empty settings as `[]`, which can't be spread into
    const settings = Array.isArray(step.settings) ? {} : (step.settings ?? {});

    try {
      await actionClient.post(storeStep().url, {
        planUid: plan.uid,
        step: {...step, settings: {...settings, ...values}},
      });
      showSuccess(t('Import step saved.'));
      router.reload({only: ['editableImportPlans']});
    } catch (error: any) {
      showError(
        error?.response?.data?.message ?? t('Couldn’t save import step.')
      );
    }
  }

  /** Keeps the plans' running state current while one runs, so a finished run gets its button back. */
  const poll = usePoll(
    2000,
    {only: ['editableImportPlans', 'nonEditableImportPlans']},
    {autoStart: false}
  );

  watch(
    () =>
      [...props.editableImportPlans, ...props.nonEditableImportPlans].some(
        (plan) => plan.running
      ),
    (running) => (running ? poll.start() : poll.stop()),
    {immediate: true}
  );

  /** Runs a plan without leaving the plans, for those who can run them. */
  function backgroundRun(plan: ImportPlan): ActionItems {
    return props.canTrigger
      ? [
          {
            type: 'button',
            label: t('Run in the background'),
            disabled: isRunning(plan),
            onClick: () => startRun(plan, true),
          },
        ]
      : [];
  }

  function editableActions(plan: ImportPlan): ActionItems {
    const actions: ActionItems = [
      {type: 'link', label: t('Edit'), href: planUrl(plan)},
      {type: 'link', label: t('View activity'), href: plan.runsUrl},
      ...backgroundRun(plan),
    ];

    if (props.canSave) {
      actions.push({
        type: 'button',
        label: t('Duplicate'),
        onClick: () => router.post(duplicate().url, {uid: plan.uid}),
      });
    }

    if (props.canDelete) {
      actions.push({
        type: 'button',
        label: t('Delete'),
        variant: 'danger',
        onClick: () => {
          if (
            !window.confirm(
              t('Are you sure you want to delete “{name}”?', {name: plan.name})
            )
          ) {
            return;
          }
          router.delete(destroy().url, {data: {uid: plan.uid}});
        },
      });
    }

    return actions;
  }

  function planActions(plan: ImportPlan): ActionItems {
    return plan.editable
      ? editableActions(plan)
      : [
          {type: 'link', label: t('View'), href: planUrl(plan)},
          {type: 'link', label: t('View activity'), href: plan.runsUrl},
          ...backgroundRun(plan),
        ];
  }

  const sortField = useLocalStorage<PlanSortField>(
    'importPlans.sortField',
    'name'
  );
  const sortDirection = useLocalStorage<PlanSortDirection>(
    'importPlans.sortDirection',
    'asc'
  );

  const collator = new Intl.Collator(undefined, {numeric: true});

  function byName(a: ImportPlan, b: ImportPlan): number {
    return collator.compare(a.name, b.name);
  }

  function lastRunTime(plan: ImportPlan): number | null {
    const date = plan.run?.dateCreated;
    return date ? Date.parse(date) : null;
  }

  function sortPlans(plans: ImportPlan[]): ImportPlan[] {
    const direction = sortDirection.value === 'asc' ? 1 : -1;

    return [...plans].sort((a, b) => {
      if (sortField.value !== 'lastRun') {
        return byName(a, b) * direction;
      }

      const aTime = lastRunTime(a);
      const bTime = lastRunTime(b);

      // plans that have never run go last, whichever way the rest are sorted
      if (aTime === null || bTime === null) {
        return (
          (aTime === null ? 1 : 0) - (bTime === null ? 1 : 0) || byName(a, b)
        );
      }

      return (aTime - bTime) * direction || byName(a, b);
    });
  }

  type PlanFilter = 'all' | 'editable' | 'code';

  const filter = ref<PlanFilter>('all');

  const allPlans = computed(() => [
    ...props.editableImportPlans,
    ...props.nonEditableImportPlans,
  ]);

  const filters = computed(
    (): Array<{
      value: PlanFilter;
      label: string;
      disabled?: boolean;
    }> => [
      {value: 'all', label: t('All')},
      {
        value: 'editable',
        label: t('Editable'),
        disabled: !props.editableImportPlans.length,
      },
      {
        value: 'code',
        label: t('Code'),
        disabled: !props.nonEditableImportPlans.length,
      },
    ]
  );

  // a filter left with nothing in it, e.g. once its last plan is deleted, shows them all again
  watch(
    () => filters.value.find((chip) => chip.value === filter.value)?.disabled,
    (disabled) => {
      if (disabled) {
        filter.value = 'all';
      }
    }
  );

  const plans = computed(() =>
    sortPlans(
      filter.value === 'editable'
        ? props.editableImportPlans
        : filter.value === 'code'
          ? props.nonEditableImportPlans
          : allPlans.value
    )
  );
</script>

<template>
  <LayoutSlot v-if="canSave" name="content-actions">
    <CpButtonLink variant="primary" icon="plus" :href="create()">
      {{ t('New import plan') }}
    </CpButtonLink>
  </LayoutSlot>

  <CpContainer>
    <template #full>
      <PlanList
        v-if="plans.length"
        class="mt-md"
        :plans="plans"
        :label="t('Import plans')"
        :can-trigger="canTrigger"
        :bulk-actions="bulkActions"
        :actions="planActions"
        :plan-url="planUrl"
        :step-url="stepUrl"
        :is-running="isRunning"
        :can-edit-mapping="canEditMapping"
        :opening-mapping="openingMapping"
        @run="startRun"
        @edit-mapping="editMapping"
      >
        <template
          v-if="editableImportPlans.length && nonEditableImportPlans.length"
          #toolbar-filters
        >
          <div
            role="group"
            :aria-label="t('Show import plans')"
            class="plan-filters"
          >
            <craft-button
              v-for="option in filters"
              :key="option.value"
              type="button"
              size="small"
              :variant="ButtonVariant.Plain"
              :aria-pressed="filter === option.value ? 'true' : 'false'"
              .active="filter === option.value"
              .disabled="option.disabled"
              @click="filter = option.value"
            >
              {{ option.label }}
            </craft-button>
          </div>
        </template>
        <template #toolbar-actions>
          <PlanSortMenu
            v-model:field="sortField"
            v-model:direction="sortDirection"
          />
        </template>
      </PlanList>
      <craft-empty
        v-else
        appearance="fill"
        :label="t('No import plans yet.')"
        icon="light/upload"
      />
    </template>
  </CpContainer>
</template>

<style scoped>
  .plan-filters {
    --c-button-spacing-inline: var(--c-spacing-md);
    display: flex;
    flex-wrap: wrap;
    gap: var(--c-spacing-sm);
  }
</style>
