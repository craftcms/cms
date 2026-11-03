<script setup lang="ts">
  /**
   * An import plan: its name and handle, the ordered list of steps that make it up, and a
   * review of what running it would do.
   *
   * The steps are a control of the form, a tab each. They're posted with the rest of the
   * form when the screen is saved; nothing about a step reaches the database before that.
   */
  import '@craftcms/ui/components/button/button';
  import {computed, ref, watch} from 'vue';
  import {router} from '@inertiajs/vue3';
  import type {UrlMethodPair} from '@inertiajs/core';
  import {t} from '@craftcms/ui';
  import LayoutSlot from '@/common/components/LayoutSlot.vue';
  import type {FormAction} from '@/common/types';
  import UiPage from '@/pages/Ui.vue';
  import type {UiPayload, UiValue} from '@/modules/ui/types';
  import type {ImportStep} from '@/modules/import/mapping/types';
  import {cloneSteps, dirtyState} from '@/modules/import/mapping/paths';
  import ImportStepComponent from '@/modules/import/ImportStep.vue';
  import ImportPlanReview from '@/modules/import/ImportPlanReview.vue';
  import StepEditor from '@/modules/import/steps/StepEditor.vue';

  // the Run import button renders beside the page, so the page takes the attributes itself
  defineOptions({
    inheritAttrs: false,
  });

  type ViewModel = CraftCms.Cms.Http.ViewModels.ImportPlanEditViewModel;

  const props = defineProps<{
    ui: UiPayload;
    submit: UrlMethodPair;
    importerTypes: ViewModel['importerTypes'];
    stepPageUrls: Record<string, string>;
    run: ViewModel['run'];
    formActions?: FormAction[];
  }>();

  function asSteps(value: UiValue | undefined): ImportStep[] {
    return Array.isArray(value) ? (value as unknown as ImportStep[]) : [];
  }

  const planUid = computed(() => (props.ui.values.uid as string) || null);

  /** The steps as they stand in the form, which Run import checks against the saved ones. */
  const currentSteps = ref(asSteps(props.ui.values.steps));

  watch(
    () => props.ui,
    (ui) => {
      currentSteps.value = asSteps(ui.values.steps);
    }
  );

  // running uses the saved plan, so unsaved steps would be left out
  const runDisabled = computed(
    () =>
      !currentSteps.value.some((step) => step.enabled) ||
      dirtyState(currentSteps.value) !==
        dirtyState(asSteps(props.ui.values.steps))
  );

  function setSteps(
    steps: ImportStep[],
    setValue: (value: UiValue, kind: 'discrete') => void
  ): void {
    currentSteps.value = cloneSteps(steps);
    setValue(cloneSteps(steps) as unknown as UiValue, 'discrete');
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

  function runImport(): void {
    const run = props.run;

    if (
      !run ||
      !window.confirm(t('Are you sure you want to run this import plan?'))
    ) {
      return;
    }

    router.post(run.url, {uid: run.uid, redirect: run.redirect});
  }

  function typeLabel(step: ImportStep): string {
    return (
      props.importerTypes.find((type) => type.value === step.type)?.label ??
      t('Choose an importer')
    );
  }
</script>

<template>
  <LayoutSlot v-if="run" name="footer-meta">
    <craft-button
      type="button"
      .disabled="runDisabled"
      :title="
        runDisabled && currentSteps.length
          ? t('Save the import plan to run it.')
          : undefined
      "
      @click="runImport"
    >
      {{ t('Run import') }}
    </craft-button>
  </LayoutSlot>

  <UiPage v-bind="$attrs" :ui="ui" :submit="submit" :form-actions="formActions">
    <template #steps="{value, setValue, editable, errors}">
      <div class="grid gap-sm">
        <template v-for="(step, index) in value" :key="step.uid">
          <StepEditor :step="step" :editable="editable" :errors="[]" />
        </template>

        <craft-button variant="dashed" icon="plus">{{
          t('Add Step')
        }}</craft-button>
      </div>
      <!--      <StepTabs-->
      <!--        :model-value="asSteps(value)"-->
      <!--        :editable="editable"-->
      <!--        :importer-types="importerTypes"-->
      <!--        :errors="stepErrors(errors)"-->
      <!--        @update:model-value="-->
      <!--          setSteps($event, (next, kind) => setValue(next, kind))-->
      <!--        "-->
      <!--      />-->
    </template>
    <template #review>
      <ImportPlanReview :steps="currentSteps" :plan-uid="planUid" />
    </template>
  </UiPage>
</template>
