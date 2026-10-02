<script setup lang="ts">
  /**
   * An import: its name and handle, and the ordered list of steps that make it up.
   *
   * The steps are a control of the form, edited in slideouts and posted with the rest
   * of the form when the screen is saved — nothing about a step reaches the database
   * before that.
   */
  import type {UrlMethodPair} from '@inertiajs/core';
  import FormPage from '@/pages/Form.vue';
  import type {FormPayload, FormValue} from '@/modules/forms/types';
  import type {StepPayload} from '@/modules/import/mapping/types';
  import {cloneSteps} from '@/modules/import/mapping/paths';
  import StepList from '@/modules/import/steps/StepList.vue';

  const props = defineProps<{
    form: FormPayload;
    submit: UrlMethodPair;
    importerTypes: Array<{value: string; label: string}>;
    stepSettingsUrl: string | null;
    validateStepUrl: string | null;
    stepMappingUrl: string;
    nestedColsUrl: string;
  }>();

  const urls = {
    settingsUrl: props.stepSettingsUrl ?? props.stepMappingUrl,
    validateUrl: props.validateStepUrl,
    mappingUrl: props.stepMappingUrl,
    nestedColsUrl: props.nestedColsUrl,
  };

  function asSteps(value: FormValue): StepPayload[] {
    return Array.isArray(value) ? (value as unknown as StepPayload[]) : [];
  }

  /**
   * Keys the errors about individual steps (`steps.<uid>.*`) the way the list expects.
   * An error about the list as a whole is left to the field.
   */
  function stepErrors(errors: FormPayload['errors']): Record<string, string[]> {
    return Object.fromEntries(
      errors
        .filter((error) => error.path[0] === 'steps' && error.path.length > 1)
        .map((error) => [error.path.join('.'), error.messages])
    );
  }
</script>

<template>
  <FormPage :form="form" :submit="submit">
    <template #steps="{value, setValue, editable, errors}">
      <StepList
        :model-value="asSteps(value)"
        :urls="urls"
        :editable="editable"
        :importer-types="importerTypes"
        :errors="stepErrors(errors)"
        @update:model-value="
          setValue(cloneSteps($event) as unknown as FormValue, 'discrete')
        "
      />
    </template>
  </FormPage>
</template>
