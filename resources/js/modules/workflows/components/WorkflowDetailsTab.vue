<script setup lang="ts">
  import {ButtonVariant, t} from '@craftcms/ui';
  import {computed, inject} from 'vue';
  import type {
    ElementEditorActions,
    ElementEditPayload,
    ElementEditPayloadUpdater,
  } from '@/modules/elements/composables/useElementEditor';
  import {elementFormActionSubmitterKey} from '@/modules/elements/composables/useElementEditor';
  import WorkflowDraftReviews from './WorkflowDraftReviews.vue';
  import WorkflowReviewPanel from './WorkflowReviewPanel.vue';

  const props = defineProps<{
    payload: ElementEditPayload;
    updatePayload: ElementEditPayloadUpdater;
  }>();

  const submitAction = inject(elementFormActionSubmitterKey);

  if (!submitAction) {
    throw new Error('WorkflowDetailsTab requires an element editor.');
  }

  const applyDraftAction = computed(() =>
    props.payload.workflow.current?.canApply
      ? (props.payload.editorActions.buttons.find(
          (action) => action.actionUrl === props.payload.applyDraftUrl
        ) ?? null)
      : null
  );

  function updateWorkflowReview(
    workflowReview: CraftCms.Cms.Workflow.Data.WorkflowReviewData,
    editorActions: ElementEditorActions
  ): void {
    props.updatePayload((payload) => ({
      workflow: {
        ...payload.workflow,
        current: workflowReview,
      },
      editorActions,
    }));
  }
</script>

<template>
  <div class="workflow-details-tab">
    <div
      v-if="
        payload.workflow.current &&
        payload.draftId !== null &&
        !payload.isProvisionalDraft
      "
      class="workflow-details-tab__review"
    >
      <WorkflowReviewPanel
        :review="payload.workflow.current"
        :element-type="payload.elementType"
        :element-id="payload.canonicalId"
        :draft-id="payload.draftId"
        :site-id="payload.siteId"
        @review-updated="updateWorkflowReview"
      >
        <div v-if="applyDraftAction">
          <craft-button
            type="button"
            :variant="ButtonVariant.Primary"
            @click="submitAction(applyDraftAction)"
          >
            {{ applyDraftAction.label }}
          </craft-button>
        </div>
      </WorkflowReviewPanel>
    </div>

    <section
      v-if="payload.workflow.draftReviews.length"
      class="workflow-details-tab__drafts"
    >
      <h3>{{ t('Drafts in review') }}</h3>
      <WorkflowDraftReviews :drafts="payload.workflow.draftReviews" />
    </section>
  </div>
</template>

<style scoped>
  .workflow-details-tab,
  .workflow-details-tab__review {
    display: grid;
  }

  .workflow-details-tab__drafts {
    display: grid;
    gap: var(--c-spacing-sm);
    padding: var(--c-spacing-md);
  }

  .workflow-details-tab__review + .workflow-details-tab__drafts {
    border-block-start: 1px solid var(--c-color-neutral-border-quiet);
  }

  .workflow-details-tab__drafts h3 {
    margin: 0;
    font-size: var(--c-text-sm);
  }
</style>
