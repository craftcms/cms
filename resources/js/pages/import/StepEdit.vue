<script setup lang="ts">
  /**
   * One import step on a page of its own: the same form as its slideout, saved straight
   * into its plan rather than handed back to the plan's screen.
   */
  import type {RequestPayload, UrlMethodPair} from '@inertiajs/core';
  import {router} from '@inertiajs/vue3';
  import {destroyStep} from '@actions/Import/ImportPlansController';
  import {t} from '@craftcms/ui';
  import type {ImportStep} from '@/modules/import/mapping/types';
  import {
    StepSaveError,
    type StepSlideoutContext,
    type StepUiPayload,
  } from '@/modules/import/steps/step-slideout';
  import StepSlideout from '@/modules/import/steps/StepSlideout.vue';

  const props = defineProps<{
    title: string;
    planUid: string;
    step: ImportStep;
    stepUi: StepUiPayload;
    mappingUrl: string;
    editable: boolean;
    submit: UrlMethodPair;
  }>();

  /**
   * Saves the step into its plan, then goes back to the plan unless continuing to edit.
   * Saving checks the whole plan, so it can fail on another step even once this one is
   * valid; the form is kept as it is, edits and all, to show why.
   */
  function save(
    step: ImportStep,
    _summary: unknown,
    {continueEditing = false} = {}
  ): Promise<void> {
    return new Promise((resolve, reject) => {
      let saved = false;

      router.visit(props.submit.url, {
        method: props.submit.method,
        // a step's settings are open-ended, which Inertia's payload type can't describe
        data: {
          planUid: props.planUid,
          step,
          continueEditing,
        } as unknown as RequestPayload,
        preserveScroll: true,
        preserveState: 'errors',
        onSuccess: () => {
          saved = true;
        },
        onError: (errors) => reject(new StepSaveError(errors)),
        // settles a visit that was canceled or failed without validation errors, too
        onFinish: () => (saved ? resolve() : reject(new StepSaveError({}))),
      });
    });
  }

  function deleteStep(): void {
    if (
      !window.confirm(t('Are you sure you want to delete this import step?'))
    ) {
      return;
    }

    router.post(destroyStep().url, {
      planUid: props.planUid,
      uid: props.step.uid,
    });
  }

  const context: StepSlideoutContext = {
    step: props.step,
    payload: props.stepUi.ui,
    canMap: props.stepUi.canMap,
    sourceError: props.stepUi.sourceError,
    summary: props.stepUi.summary,
    editable: props.editable,
    mappingUrl: props.mappingUrl,
    submitLabel: t('Save'),
    canContinueEditing: true,
    formActions: props.editable
      ? [
          {
            label: t('Delete step'),
            icon: 'trash',
            variant: 'danger',
            onClick: deleteStep,
          },
        ]
      : [],
    apply: save,
  };
</script>

<template>
  <StepSlideout :context="context" :title="title" />
</template>
