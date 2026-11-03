<script setup lang="ts">
  /**
   * One of the plan's steps, in a slideout over the plan: its settings, what it matches
   * existing elements on, and its mapping. Applying it checks the step and hands it back
   * to the plan's form, which saves it with the rest of the plan.
   */
  import {shallowRef} from 'vue';
  import {useForm} from '@inertiajs/vue3';
  import {t} from '@craftcms/ui';
  import CpContainer from '@/common/components/CpContainer.vue';
  import {useAppLayout} from '@/common/composables/useAppLayout';
  import {useSlideout} from '@/common/slideouts';
  import type {UiPayload} from '@/modules/ui/types';
  import type {ImportStep} from '@/modules/import/mapping/types';
  import {cloneStep, dirtyState} from '@/modules/import/mapping/paths';
  import StepEditor from './StepEditor.vue';
  import {takeStepSlideoutContext, validateStep} from './step-slideout';

  const props = defineProps<{
    contextId: string;
    title: string;
  }>();

  const context = takeStepSlideoutContext(props.contextId);
  const slideout = useSlideout();
  const step = shallowRef<ImportStep>(context.step);
  const errors = shallowRef<UiPayload['errors']>(context.errors);

  // backs the Apply button, and tells the panel whether closing would lose changes
  const form = useForm({state: dirtyState(context.step)});

  function onUpdate(next: ImportStep): void {
    step.value = next;
    form.state = dirtyState(next);
  }

  async function apply(): Promise<void> {
    if (context.editable) {
      try {
        await validateStep(step.value);
      } catch (error: any) {
        const responseErrors = error?.response?.data?.errors as
          | Record<string, string[]>
          | undefined;

        if (!responseErrors) {
          throw error;
        }

        errors.value = Object.entries(responseErrors).map(
          ([path, messages]) => ({path: path.split('.'), messages})
        );

        return;
      }
    }

    context.apply(cloneStep(step.value));

    // before close(): closing drops the panel from the store, and its handler with it
    slideout?.saved();
    slideout?.close({force: true});
  }

  useAppLayout(() => ({
    title: props.title,
    editUrl: context.editUrl,
    form: context.editable ? form : null,
    submitButtonLabel: t('Apply'),
    defaultFormActions: [],
    onSave: apply,
  }));
</script>

<template>
  <CpContainer>
    <div class="step-slideout">
      <StepEditor
        :step="context.step"
        :editable="context.editable"
        :errors="errors"
        @update:step="onUpdate"
      />
    </div>
  </CpContainer>
</template>

<style scoped>
  .step-slideout {
    padding-block: var(--c-spacing-lg);
  }
</style>
