<script setup lang="ts">
  import {router} from '@inertiajs/vue3';
  import type {ActionItem} from '@/common/types';
  import {useAppLayout} from '@/common/composables/useAppLayout';
  import type {FormPayload, FormValue} from '@/modules/forms/types';
  import WorkflowStagesInput from '@/modules/workflows/components/WorkflowStagesInput.vue';
  import FormPage from '@/pages/Form.vue';

  type Props = CraftCms.Cms.Http.ViewModels.WorkflowEditViewModel;
  type WorkflowStage = CraftCms.Cms.Workflow.Data.WorkflowStageData;

  const props = defineProps<Props>();
  const formPayload = props.form as unknown as FormPayload;
  const actions: ActionItem[] = props.deleteAction
    ? [
        {
          label: props.deleteAction.label,
          variant: 'danger',
          onClick: () => {
            if (window.confirm(props.deleteAction!.confirm)) {
              router.delete(props.deleteAction!.url);
            }
          },
        },
      ]
    : [];

  useAppLayout({formActions: actions});

  function stages(value: FormValue): WorkflowStage[] {
    return Array.isArray(value) ? (value as WorkflowStage[]) : [];
  }
</script>

<template>
  <FormPage :form="formPayload" :submit="submit">
    <template #stages="{value, setValue, editable}">
      <WorkflowStagesInput
        :model-value="stages(value)"
        :stage-types="stageTypes"
        :editable="editable"
        @update:model-value="setValue($event, 'discrete')"
      />
    </template>
  </FormPage>
</template>
