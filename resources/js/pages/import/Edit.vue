<script setup lang="ts">
  /**
   * An import: its name and handle, and the ordered list of steps that make it up.
   *
   * The steps are a control of the form, edited in slideouts and posted with the rest
   * of the form when the screen is saved — nothing about a step reaches the database
   * before that.
   */
  import type {UrlMethodPair} from '@inertiajs/core';
  import type {FormAction} from '@/common/types';
  import UiPage from '@/pages/Ui.vue';
  import type {UiPayload, UiValue} from '@/modules/ui/types';
  import type {ImportStep} from '@/modules/import/mapping/types';
  import {cloneSteps} from '@/modules/import/mapping/paths';
  import StepList from '@/modules/import/steps/StepList.vue';

  type ImporterType =
    CraftCms.Cms.Http.ViewModels.ImportPlanEditViewModel['importerTypes'][number];

  defineProps<{
    ui: UiPayload;
    submit: UrlMethodPair;
    importerTypes: ImporterType[];
    formActions?: FormAction[];
  }>();

  function asSteps(value: UiValue): ImportStep[] {
    return Array.isArray(value) ? (value as unknown as ImportStep[]) : [];
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
        :errors="stepErrors(errors)"
        @update:model-value="
          setValue(cloneSteps($event) as unknown as UiValue, 'discrete')
        "
      />
    </template>
  </UiPage>
</template>
