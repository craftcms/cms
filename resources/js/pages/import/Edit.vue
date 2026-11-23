<script setup lang="ts">
  /**
   * An import: its name and handle, and the ordered list of steps that make it up.
   *
   * The steps are a control of the form, edited in slideouts and posted with the rest
   * of the form when the screen is saved — nothing about a step reaches the database
   * before that.
   */
  import type {UrlMethodPair} from '@inertiajs/core';
  import '@craftcms/ui/components/button/button';
  import {ButtonVariant, t, type BaseAction} from '@craftcms/ui';
  import {router} from '@inertiajs/vue3';
  import {computed, shallowRef} from 'vue';
  import {run as runAction} from '@actions/Import/ImportPlansController';
  import type {ActionItemButton, FormAction} from '@/common/types';
  import UiPage from '@/pages/Ui.vue';
  import type {UiPayload, UiValue} from '@/modules/ui/types';
  import type {ImportStep} from '@/modules/import/mapping/types';
  import {cloneSteps} from '@/modules/import/mapping/paths';
  import StepList from '@/modules/import/steps/StepList.vue';
  import PlanPreview from '@/modules/import/preview/PlanPreview.vue';
  import LayoutSlot from '@/common/components/LayoutSlot.vue';
  import RunsPanel from '@/modules/import/run/RunsPanel.vue';
  import {useRunState, type RunState} from '@/modules/import/run/run-state';

  type ImporterType =
    CraftCms.Cms.Http.ViewModels.ImportPlanEditViewModel['importerTypes'][number];

  const props = defineProps<{
    ui: UiPayload;
    submit: UrlMethodPair;
    importerTypes: ImporterType[];
    stepEditUrls: Record<string, string>;
    stepMappingUrls: Record<string, string>;
    stepSummaries: Record<string, CraftCms.Cms.Import.Data.StepSummary>;
    formActions?: FormAction[];
    runState: RunState | null;
    earlierRuns?: RunState[];
    /** How many runs the plan has kept, and where they're listed. Missing until it's saved. */
    runs?: {count: number; url: string} | null;
    /** Whether the plan can be run from here. Missing until it's saved. */
    canTrigger?: boolean;
    /** Deletes the plan once it's confirmed. Missing when it can't be deleted. */
    deleteAction?: BaseAction | null;
    /** Whether the plan is only shown, as one registered in code is. */
    readOnly?: boolean;
  }>();

  /** The plan as it's saved, or registered: its handle field can be edited before then. */
  const savedHandle =
    typeof props.ui.values.handle === 'string' &&
    (props.ui.values.uid || props.readOnly)
      ? props.ui.values.handle
      : null;
  const run = useRunState(() => savedHandle, props.runState);

  function asSteps(value: UiValue | undefined): ImportStep[] {
    return Array.isArray(value) ? (value as unknown as ImportStep[]) : [];
  }

  const planName = String(props.ui.values.name ?? '');
  /** Whether a run has been asked for that the page doesn't know about yet. */
  const starting = shallowRef(false);
  const running = computed(
    () => run.value?.status === 'pending' || run.value?.status === 'running'
  );

  /** Runs the plan as it's saved, then follows the run to its page. */
  function startRun(): void {
    if (
      !savedHandle ||
      !window.confirm(
        t('Are you sure you want to run “{name}”?', {name: planName})
      )
    ) {
      return;
    }

    router.post(
      runAction().url,
      props.ui.values.uid ? {uid: props.ui.values.uid} : {handle: savedHandle},
      {
        onStart: () => (starting.value = true),
        onFinish: () => (starting.value = false),
      }
    );
  }

  /** Runs the plan as it's saved, beside the Save button that can save and run it. */
  const additionalButtons = computed<ActionItemButton[]>(() =>
    props.canTrigger
      ? [
          {
            label: t('Run plan'),
            disabled: starting.value || running.value,
            onClick: startRun,
          },
        ]
      : []
  );

  /** The steps as the form has them, for the preview. Only the list of steps changes them. */
  const steps = shallowRef(asSteps(props.ui.values.steps));
  /** Each step at a glance, kept up to date by the list as steps are applied, for the preview's tabs too. */
  const stepSummaries = shallowRef(props.stepSummaries);

  function updateSteps(
    value: ImportStep[],
    setValue: (value: UiValue, mode: 'discrete') => void
  ): void {
    steps.value = cloneSteps(value);
    setValue(cloneSteps(value) as unknown as UiValue, 'discrete');
  }

  /**
   * Keys the errors about individual steps (`steps.<uid>.*`) the way the list expects.
   * An error about the list as a whole is left to the field.
   */
  function stepErrors(errors: UiPayload['errors']): Record<string, string[]> {
    return Object.fromEntries(
      errors
        .filter((error) => error.path[0] === 'steps' && error.path.length > 1)
        .map((error) => [error.path.join('.'), error.messages])
    );
  }
</script>

<template>
  <LayoutSlot v-if="deleteAction" name="additional-buttons">
    <craft-button
      type="button"
      :variant="ButtonVariant.DangerPlain"
      .action="deleteAction"
    >
      {{ t('Delete') }}
    </craft-button>
  </LayoutSlot>

  <RunsPanel
    v-if="runs"
    :runs="run ? [run, ...(earlierRuns ?? [])] : []"
    :count="runs.count"
    :url="runs.url"
  />

  <UiPage
    :ui="ui"
    :submit="submit"
    :content-max-width="false"
    :form-actions="formActions"
    :form-additional-buttons="additionalButtons"
  >
    <template #steps="{value, setValue, editable, errors}">
      <StepList
        :model-value="asSteps(value)"
        :editable="editable"
        :importer-types="importerTypes"
        v-model:step-summaries="stepSummaries"
        :step-edit-urls="stepEditUrls"
        :step-mapping-urls="stepMappingUrls"
        :errors="stepErrors(errors)"
        @update:model-value="updateSteps($event, setValue)"
      />
    </template>
    <template #preview>
      <PlanPreview
        :steps="steps"
        :importer-types="importerTypes"
        :step-summaries="stepSummaries"
      />
    </template>
  </UiPage>
</template>
