<script setup lang="ts">
  /**
   * One import step's own edit page: its settings, what it matches existing elements on,
   * and its mapping. Saving it saves the step into its plan, as an element's edit page
   * saves the element.
   */
  import {computed} from 'vue';
  import {useForm} from '@inertiajs/vue3';
  import type {UrlMethodPair} from '@inertiajs/core';
  import {t} from '@craftcms/ui';
  import CpContainer from '@/common/components/CpContainer.vue';
  import {useAppLayout} from '@/common/composables/useAppLayout';
  import {useSettingsSave} from '@/modules/settings/composables/useSettingsSave';
  import type {UiPayload} from '@/modules/ui/types';
  import type {ImportStep} from '@/modules/import/mapping/types';
  import StepEditor from '@/modules/import/steps/StepEditor.vue';

  const props = defineProps<{
    title: string;
    planUid: string;
    step: ImportStep;
    editable: boolean;
    submit: UrlMethodPair;
    /** The step's own page, for a slideout's open-in-a-new-tab link. Unset for a new step. */
    editUrl?: string | null;
  }>();

  // a step's settings are open-ended, which Inertia's form data typing can't describe
  const form = useForm<Record<string, any>>({step: props.step});

  const {save} = useSettingsSave(form, () => props.submit, {
    transform: (data) => ({planUid: props.planUid, step: data.step}),
  });

  /**
   * The server's errors about this step, by their path within it. They're keyed by the
   * step's uid in the plan, which is a new one when the step was saved as new.
   */
  const stepErrors = computed<UiPayload['errors']>(() =>
    Object.entries(form.errors as Record<string, string>)
      .filter(([path]) => path.startsWith('steps.'))
      .map(([path, message]) => ({
        path: path.split('.').slice(2),
        messages: [message],
      }))
      .filter((error) => error.path.length > 0)
  );

  /** The plan's own errors, such as one about its steps as a whole. */
  const planErrors = computed(() =>
    Object.entries(form.errors as Record<string, string>)
      .filter(([path]) => path === 'steps' || !path.startsWith('steps.'))
      .map(([, message]) => message)
  );

  useAppLayout(() => ({
    title: props.title,
    editUrl: props.editUrl ?? null,
    form: props.editable ? form : null,
    onSave: save,
    formActions: props.editable
      ? [
          {
            label: t('Save as a new step'),
            onClick: () => save({redirect: false, data: {asNew: true}}),
          },
        ]
      : [],
  }));
</script>

<template>
  <CpContainer>
    <div class="step-edit">
      <ul v-if="planErrors.length" class="error-list" role="alert">
        <li v-for="message in planErrors" :key="message">{{ message }}</li>
      </ul>
      <StepEditor
        :step="step"
        :editable="editable"
        :errors="stepErrors"
        @update:step="form.step = $event"
      />
    </div>
  </CpContainer>
</template>

<style scoped>
  .step-edit {
    display: flex;
    flex-direction: column;
    gap: var(--c-spacing-lg);
    padding-block: var(--c-spacing-lg);
  }
</style>
