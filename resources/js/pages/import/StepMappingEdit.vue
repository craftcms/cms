<script setup lang="ts">
  /**
   * One import step's mapping on a page of its own: the same table as its slideout,
   * saved straight into its plan rather than handed back to the step.
   */
  import '@craftcms/ui/components/callout/callout';
  import type {RequestPayload, UrlMethodPair} from '@inertiajs/core';
  import {router} from '@inertiajs/vue3';
  import {t} from '@craftcms/ui';
  import CpContainer from '@/common/components/CpContainer.vue';
  import {useAppLayout} from '@/common/composables/useAppLayout';
  import type {ImportStep, MappingValues} from '@/modules/import/mapping/types';
  import {
    initialMappingState,
    type StepMappingContext,
    type StepMappingPayload,
  } from '@/modules/import/steps/step-mapping';
  import StepMapping from '@/modules/import/steps/StepMapping.vue';

  const props = defineProps<{
    title: string;
    planUid: string;
    step: ImportStep;
    mapping: StepMappingPayload;
    stepUrl: string;
    editable: boolean;
    submit: UrlMethodPair;
  }>();

  /** Saves the step with the mapping into its plan, which goes back to the plan. */
  function save(values: MappingValues): Promise<void> {
    // PHP sends empty settings as `[]`, which can't be spread into
    const settings = Array.isArray(props.step.settings)
      ? {}
      : (props.step.settings ?? {});

    return new Promise((resolve, reject) => {
      router.visit(props.submit.url, {
        method: props.submit.method,
        // a step's settings are open-ended, which Inertia's payload type can't describe
        data: {
          planUid: props.planUid,
          step: {...props.step, settings: {...settings, ...values}},
        } as unknown as RequestPayload,
        preserveScroll: true,
        preserveState: 'errors',
        onSuccess: () => resolve(),
        onError: () => reject(new Error('The import step couldn’t be saved.')),
        onCancel: () => reject(new Error('The import step wasn’t saved.')),
      });
    });
  }

  const context: StepMappingContext | null = props.mapping.available
    ? {
        ...initialMappingState(props.mapping),
        destinationCols: props.mapping.destinationCols ?? [],
        sourceDataCols: props.mapping.sourceDataCols ?? [],
        templateTextExpanderTriggers: (props.mapping
          .templateTextExpanderTriggers ??
          []) as StepMappingContext['templateTextExpanderTriggers'],
        editable: props.editable,
        step: props.step,
        submitLabel: t('Save'),
        apply: save,
      }
    : null;

  // the mapping sets up the screen itself; without one there's only the reason why
  if (!context) {
    useAppLayout({title: props.title});
  }
</script>

<template>
  <StepMapping v-if="context" :context="context" :title="title" />
  <CpContainer v-else class="py-md">
    <craft-callout variant="warning" icon="triangle-exclamation">
      {{ mapping.message }}
      <a :href="stepUrl">{{ t('Edit import step') }}</a>
    </craft-callout>
  </CpContainer>
</template>
