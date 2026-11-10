<script setup lang="ts">
  /**
   * An import: its name and handle, and the ordered list of steps that make it up.
   *
   * The steps are a control of the form, edited in slideouts and posted with the rest
   * of the form when the screen is saved — nothing about a step reaches the database
   * before that.
   */
  import type {UrlMethodPair} from '@inertiajs/core';
  import {shallowRef} from 'vue';
  import type {FormAction} from '@/common/types';
  import UiPage from '@/pages/Ui.vue';
  import type {UiPayload, UiValue} from '@/modules/ui/types';
  import type {ImportStep} from '@/modules/import/mapping/types';
  import {cloneSteps} from '@/modules/import/mapping/paths';
  import StepList from '@/modules/import/steps/StepList.vue';
  import PlanPreview from '@/modules/import/preview/PlanPreview.vue';

  type ImporterType =
    CraftCms.Cms.Http.ViewModels.ImportPlanEditViewModel['importerTypes'][number];

  const props = defineProps<{
    ui: UiPayload;
    submit: UrlMethodPair;
    importerTypes: ImporterType[];
    stepEditUrls: Record<string, string>;
    stepSummaries: Record<string, CraftCms.Cms.Import.Data.StepSummary>;
    formActions?: FormAction[];
  }>();

  function asSteps(value: UiValue | undefined): ImportStep[] {
    return Array.isArray(value) ? (value as unknown as ImportStep[]) : [];
  }

  /** The steps as the form has them, for the preview. Only the list of steps changes them. */
  const steps = shallowRef(asSteps(props.ui.values.steps));

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
  <UiPage :ui="ui" :submit="submit" :form-actions="formActions">
    <template #steps="{value, setValue, editable, errors}">
      <StepList
        :model-value="asSteps(value)"
        :editable="editable"
        :importer-types="importerTypes"
        :step-edit-urls="stepEditUrls"
        :step-summaries="stepSummaries"
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
