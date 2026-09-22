<script setup lang="ts">
  import {t, type ActionMenuItem} from '@craftcms/ui';
  import {computed, watch} from 'vue';
  import controller from '@/actions/CraftCms/Cms/Http/Controllers/Workflows/WorkflowTransitionsController';
  import {useFlashMessages} from '@/common/composables/useFlashMessages';
  import type {
    ElementEditPayload,
    ElementEditPayloadUpdater,
  } from '@/modules/elements/composables/useElementEditor';
  import {
    useWorkflowTransition,
    type WorkflowIdentity,
  } from '../composables/useWorkflowTransition';

  type WorkflowReviewData = CraftCms.Cms.Workflow.Data.WorkflowReviewData;

  const props = defineProps<{
    payload: ElementEditPayload;
    updatePayload: ElementEditPayloadUpdater;
  }>();

  const review = computed(() => props.payload.workflow.current);

  function identity(): WorkflowIdentity {
    return {
      elementType: props.payload.elementType,
      elementId: props.payload.canonicalId,
      draftId: props.payload.draftId,
      siteId: props.payload.siteId,
    };
  }

  function updateReview(
    workflowReview: WorkflowReviewData,
    editorActions: ElementEditPayload['editorActions']
  ): void {
    props.updatePayload((payload) => ({
      workflow: {
        ...payload.workflow,
        current: workflowReview,
      },
      editorActions,
    }));
  }

  const {error, processing, transition} = useWorkflowTransition(
    identity,
    updateReview
  );
  const {flash} = useFlashMessages();

  watch(error, (message) => {
    if (message) {
      flash('error', message);
    }
  });

  const actions = computed<ActionMenuItem[]>(() => {
    if (!review.value) {
      return [];
    }

    return [
      ...(review.value.canOverride
        ? [
            {
              label: t('Override approval'),
              variant: 'danger',
              disabled: processing.value,
              onClick: overrideApproval,
            },
          ]
        : []),
      ...(review.value.canRestart
        ? [
            {
              label: t('Restart workflow'),
              variant: 'danger',
              disabled: processing.value,
              onClick: restartWorkflow,
            },
          ]
        : []),
    ];
  });

  async function overrideApproval(): Promise<void> {
    if (processing.value || !review.value) {
      return;
    }

    if (
      !window.confirm(
        t(
          'This will bypass the remaining workflow requirements and approve the draft. Are you sure?'
        )
      )
    ) {
      return;
    }

    if (review.value.runId === null) {
      error.value = t(
        'This review is no longer current. Refresh and try again.'
      );
      return;
    }

    await transition(
      controller.override.url({workflowRun: review.value.runId})
    );
  }

  async function restartWorkflow(): Promise<void> {
    if (processing.value || !review.value) {
      return;
    }

    if (
      !window.confirm(
        t(
          'This will discard all approvals and restart the workflow from its first stage. Are you sure?'
        )
      )
    ) {
      return;
    }

    if (review.value.runId === null) {
      error.value = t(
        'This review is no longer current. Refresh and try again.'
      );
      return;
    }

    await transition(controller.restart.url({workflowRun: review.value.runId}));
  }
</script>

<template>
  <craft-action-menu v-if="actions.length" .actions="actions" />
</template>
