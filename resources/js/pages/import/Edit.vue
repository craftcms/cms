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
  import {computed, shallowRef, useTemplateRef} from 'vue';
  import ErrorSummary from '@/common/form/ErrorSummary.vue';
  import type {ActionItemButton, FormAction} from '@/common/types';
  import UiPage from '@/pages/Ui.vue';
  import type {UiPayload, UiValue} from '@/modules/ui/types';
  import type {ImportStep} from '@/modules/import/mapping/types';
  import {cloneSteps} from '@/modules/import/mapping/paths';
  import StepList from '@/modules/import/steps/StepList.vue';
  import PlanPreview from '@/modules/import/preview/PlanPreview.vue';
  import LayoutSlot from '@/common/components/LayoutSlot.vue';
  import RunCallout from '@/modules/import/run/RunCallout.vue';
  import {useRunState, type RunState} from '@/modules/import/run/run-state';

  type ImporterType =
    CraftCms.Cms.Http.ViewModels.ImportPlanEditViewModel['importerTypes'][number];

  const props = defineProps<{
    ui: UiPayload;
    submit: UrlMethodPair;
    importerTypes: ImporterType[];
    stepEditUrls: Record<string, string>;
    stepSummaries: Record<string, CraftCms.Cms.Import.Data.StepSummary>;
    formActions?: FormAction[];
    runState: RunState | null;
    canRun?: boolean;
    /** Deletes the plan once it's confirmed. Missing when it can't be deleted. */
    deleteAction?: BaseAction | null;
  }>();

  /** The plan as it's saved: its handle field can be edited before then. */
  const savedHandle =
    typeof props.ui.values.handle === 'string' && props.ui.values.uid
      ? props.ui.values.handle
      : null;
  const run = useRunState(() => savedHandle, props.runState);

  function asSteps(value: UiValue | undefined): ImportStep[] {
    return Array.isArray(value) ? (value as unknown as ImportStep[]) : [];
  }

  /** The steps as the form has them, for the preview. Only the list of steps changes them. */
  const page = useTemplateRef('page');

  /** Saves, then starts a run, coming back to the plan to watch it. */
  const additionalButtons = computed<ActionItemButton[]>(() =>
    props.canRun
      ? [
          {
            label: t('Save and run'),
            onClick: () => page.value?.save?.({data: {run: true}}),
          },
        ]
      : []
  );

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

  const stepUids = computed(() =>
    steps.value.flatMap((step) => (step.uid ? [step.uid] : []))
  );
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

  <!-- fills the screen's error summary, so the form's own save errors are shown here too -->
  <LayoutSlot v-if="run" name="error-summary">
    <ErrorSummary v-if="page?.form.hasErrors" :errors="page.form.errors" />
    <RunCallout :run="run" :step-uids="stepUids" />
  </LayoutSlot>

  <UiPage
    ref="page"
    :ui="ui"
    :submit="submit"
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
        :errors="stepErrors(errors)"
        :run-steps="run?.steps ?? {}"
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
