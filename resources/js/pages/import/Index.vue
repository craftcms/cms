<script setup lang="ts">
  import {actionClient, t} from '@craftcms/ui';
  import {computed, ref, watch} from 'vue';
  import {router, usePoll} from '@inertiajs/vue3';
  import CpButtonLink from '@/common/components/CpButtonLink.vue';
  import CpContainer from '@/common/components/CpContainer.vue';
  import LayoutSlot from '@/common/components/LayoutSlot.vue';
  import type {ActionItems} from '@/common/types';
  import {showError, showSuccess} from '@/modules/messages/messages';
  import PlanList from '@/modules/import/plans/PlanList.vue';
  import type {PlanBulkAction} from '@/modules/import/plans/types';
  import {
    create,
    destroy,
    duplicate,
    edit,
    editStep,
    reorder,
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

  function planUrl(plan: ImportPlan): string | null {
    return plan.editable ? edit({handle: plan.handle}).url : null;
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

  function startRun(plan: ImportPlan): void {
    if (
      isRunning(plan) ||
      !window.confirm(
        t('Are you sure you want to run “{name}”?', {name: plan.name})
      )
    ) {
      return;
    }

    router.post(run().url, runBody(plan), {
      preserveScroll: true,
      onStart: () => markStarting([plan], true),
      onFinish: () => markStarting([plan], false),
    });
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
  function bulkActions(editable: boolean): PlanBulkAction[] {
    const actions: PlanBulkAction[] = [];

    if (props.canTrigger) {
      actions.push({
        key: 'run',
        label: t('Run'),
        perform: (plans) => void runSelected(plans),
      });
    }

    if (editable && props.canSave) {
      actions.push({
        key: 'duplicate',
        label: t('Duplicate'),
        perform: (plans) =>
          router.post(
            duplicate().url,
            {uids: plans.map((plan) => plan.uid)},
            {preserveScroll: true}
          ),
      });
    }

    if (editable && props.canDelete) {
      actions.push({
        key: 'delete',
        label: t('Delete'),
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
  }

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

  function editableActions(plan: ImportPlan): ActionItems {
    const actions: ActionItems = [
      {type: 'link', label: t('Edit'), href: planUrl(plan)!},
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

  const planUids = ref<string[]>([]);

  watch(
    () => props.editableImportPlans,
    (plans) => {
      planUids.value = plans.map((plan) => plan.uid!);
    },
    {immediate: true}
  );

  /** The editable import plans, in the order the user has put them in. */
  const editablePlans = computed(() =>
    planUids.value
      .map((uid) => props.editableImportPlans.find((plan) => plan.uid === uid))
      .filter((plan): plan is ImportPlan => plan !== undefined)
  );

  function handleReorder(startIndex: number, finishIndex: number): void {
    const previousUids = planUids.value;

    if (finishIndex < 0 || finishIndex >= previousUids.length) {
      return;
    }

    const nextUids = [...previousUids];
    const [uid] = nextUids.splice(startIndex, 1);

    if (uid === undefined) {
      return;
    }

    nextUids.splice(finishIndex, 0, uid);
    planUids.value = nextUids;

    router.post(
      reorder(),
      {uids: nextUids},
      {
        preserveScroll: true,
        preserveState: true,
        onError: () => {
          planUids.value = previousUids;
        },
      }
    );
  }
</script>

<template>
  <LayoutSlot v-if="canSave" name="content-actions">
    <CpButtonLink variant="primary" icon="plus" :href="create()">
      {{ t('New import plan') }}
    </CpButtonLink>
  </LayoutSlot>

  <div class="grid gap-lg">
    <CpContainer>
      <div class="mb-md">
        <h2>{{ t('Editable Import Plans') }}</h2>
        <p>
          {{ t('Those import plans can be edited in the control panel.') }}
        </p>
      </div>

      <template #full>
        <PlanList
          v-if="editablePlans.length"
          :plans="editablePlans"
          :label="t('Editable Import Plans')"
          :reorderable="canSave"
          :can-trigger="canTrigger"
          :bulk-actions="bulkActions(true)"
          :actions="editableActions"
          :plan-url="planUrl"
          :step-url="stepUrl"
          :is-running="isRunning"
          :can-edit-mapping="canEditMapping"
          :opening-mapping="openingMapping"
          @run="startRun"
          @reorder="handleReorder"
          @edit-mapping="editMapping"
        />
        <craft-empty
          v-else
          appearance="fill"
          :label="t('No import plans yet.')"
          icon="light/upload"
        />
      </template>
    </CpContainer>

    <CpContainer v-if="nonEditableImportPlans.length">
      <div class="mb-md">
        <h2>{{ t('Non-Editable Import Plans') }}</h2>
        <p>
          {{
            t(
              'Those import plans are registered in code and can’t be edited in the control panel.'
            )
          }}
        </p>
      </div>

      <template #full>
        <PlanList
          :plans="nonEditableImportPlans"
          :label="t('Non-Editable Import Plans')"
          :can-trigger="canTrigger"
          :bulk-actions="bulkActions(false)"
          :actions="() => []"
          :plan-url="planUrl"
          :step-url="stepUrl"
          :is-running="isRunning"
          @run="startRun"
        />
      </template>
    </CpContainer>
  </div>
</template>
