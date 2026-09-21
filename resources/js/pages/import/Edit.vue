<script setup lang="ts">
  /**
   * An import: its name and handle, and the ordered list of steps that make it up.
   *
   * The steps are held here, edited in slideouts, and posted alongside the form when
   * the screen is saved — nothing about a step reaches the database before that.
   */
  import type {UrlMethodPair} from '@inertiajs/core';
  import {usePage} from '@inertiajs/vue3';
  import {computed, ref} from 'vue';
  import FormPage from '@/pages/Form.vue';
  import type {FormPayload} from '@/modules/forms/types';
  import type {StepPayload} from '@/modules/import/mapping/types';
  import {cloneSteps} from '@/modules/import/steps/clone';
  import StepList from '@/modules/import/steps/StepList.vue';

  const props = defineProps<{
    form: FormPayload;
    submit: UrlMethodPair;
    steps: StepPayload[];
    importerTypes: Array<{value: string; label: string}>;
    stepSettingsUrl: string | null;
    validateStepUrl: string | null;
    stepMappingUrl: string;
    nestedColsUrl: string;
    readOnly: boolean;
    canSave: boolean;
  }>();

  const page = usePage<{errors?: Record<string, string | string[]>}>();

  const editable = !props.readOnly && props.canSave;
  const steps = ref<StepPayload[]>(cloneSteps(props.steps));

  const urls = {
    settingsUrl: props.stepSettingsUrl ?? props.stepMappingUrl,
    validateUrl: props.validateStepUrl,
    mappingUrl: props.stepMappingUrl,
    nestedColsUrl: props.nestedColsUrl,
  };

  const additionalData = computed(() => ({steps: steps.value}));

  // `steps` on its own is the list's error (no steps at all); `steps.<uid>.*` belongs to
  // one row. Both go to the list, which decides where each one renders.
  const stepErrors = computed(() =>
    Object.fromEntries(
      Object.entries(page.props.errors ?? {})
        .filter(([path]) => path === 'steps' || path.startsWith('steps.'))
        .map(([path, messages]) => [
          path,
          Array.isArray(messages) ? messages : [messages],
        ])
    )
  );
</script>

<template>
  <div class="grid gap-6">
    <FormPage :form="form" :submit="submit" :additional-data="additionalData" />

    <StepList
      v-model="steps"
      :urls="urls"
      :editable="editable"
      :importer-types="importerTypes"
      :errors="stepErrors"
    />
  </div>
</template>
