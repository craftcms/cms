<script setup lang="ts">
  /**
   * One import step on a page of its own: the same form as its slideout, saved straight
   * into its plan rather than handed back to the plan's screen.
   */
  import type {RequestPayload, UrlMethodPair} from '@inertiajs/core';
  import {router} from '@inertiajs/vue3';
  import {t} from '@craftcms/ui';
  import type {ImportStep} from '@/modules/import/mapping/types';
  import type {
    StepSlideoutContext,
    StepUiPayload,
  } from '@/modules/import/steps/step-slideout';
  import StepSlideout from '@/modules/import/steps/StepSlideout.vue';

  const props = defineProps<{
    title: string;
    planUid: string;
    step: ImportStep;
    stepUi: StepUiPayload;
    editable: boolean;
    submit: UrlMethodPair;
  }>();

  function save(step: ImportStep): Promise<void> {
    return new Promise((resolve) => {
      router.visit(props.submit.url, {
        method: props.submit.method,
        // a step's settings are open-ended, which Inertia's payload type can't describe
        data: {planUid: props.planUid, step} as unknown as RequestPayload,
        preserveScroll: true,
        onFinish: () => resolve(),
      });
    });
  }

  const context: StepSlideoutContext = {
    step: props.step,
    payload: props.stepUi.ui,
    canMap: props.stepUi.canMap,
    sourceError: props.stepUi.sourceError,
    summary: props.stepUi.summary,
    editable: props.editable,
    submitLabel: t('Save'),
    apply: save,
  };
</script>

<template>
  <StepSlideout :context="context" :title="title" />
</template>
