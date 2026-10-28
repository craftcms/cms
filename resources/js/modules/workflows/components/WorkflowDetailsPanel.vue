<script setup lang="ts">
  import {ButtonVariant, t} from '@craftcms/ui';
  import {computed} from 'vue';
  import type {
    ElementEditorActions,
    ElementEditPayload,
    ElementEditPayloadUpdater,
    ElementFormActionSubmitter,
  } from '@/modules/elements/composables/useElementEditor';
  import WorkflowDraftReviews from './WorkflowDraftReviews.vue';
  import WorkflowReviewPanel from './WorkflowReviewPanel.vue';
  import type {WorkflowReleaseState} from '../composables/useWorkflowTransition';
  import ReleasePicker from '@/modules/releases/components/ReleasePicker.vue';
  import {useReleaseEditorActions} from '@/modules/releases/releaseEditor';
  import type {ReleaseOption} from '@/modules/elements/composables/useElementEditor';

  const props = defineProps<{
    payload: ElementEditPayload;
    updatePayload: ElementEditPayloadUpdater;
    submitAction: ElementFormActionSubmitter;
  }>();

  const applyDraftAction = computed(() =>
    props.payload.workflow.current?.canApply
      ? (props.payload.editorActions.buttons.find(
          (action) => action.actionUrl === props.payload.applyDraftUrl
        ) ?? null)
      : null
  );
  function applyDraft(): void {
    if (applyDraftAction.value && !applyDraftAction.value.disabled) {
      props.submitAction(applyDraftAction.value);
    }
  }

  const releaseActions = useReleaseEditorActions();
  const canChooseRelease = computed(
    () =>
      releaseActions !== null &&
      props.payload.canAddToRelease &&
      (props.payload.releaseOptions.length > 0 ||
        props.payload.canCreateRelease)
  );

  function selectRelease(release: ReleaseOption): void {
    releaseActions?.select(release);
  }

  function createRelease(opener: HTMLElement | null): void {
    releaseActions?.create(opener);
  }

  function updateWorkflowReview(
    workflowReview: CraftCms.Cms.Workflow.Data.WorkflowReviewData,
    editorActions: ElementEditorActions,
    release: WorkflowReleaseState | null | undefined
  ): void {
    props.updatePayload((payload) => ({
      workflow: {
        ...payload.workflow,
        current: workflowReview,
      },
      editorActions,
      ...release,
    }));
  }
</script>

<template>
  <div class="workflow-details-panel">
    <div
      v-if="
        payload.workflow.current &&
        payload.draftId !== null &&
        !payload.isProvisionalDraft
      "
      class="workflow-details-panel__review"
    >
      <WorkflowReviewPanel
        :review="payload.workflow.current"
        :element-type="payload.elementType"
        :element-id="payload.canonicalId"
        :draft-id="payload.draftId"
        :site-id="payload.siteId"
        @review-updated="updateWorkflowReview"
      >
        <div
          v-if="applyDraftAction || canChooseRelease"
          class="flex flex-wrap justify-end gap-2"
        >
          <template v-if="applyDraftAction">
            <craft-button
              id="workflow-apply-draft"
              type="button"
              :variant="ButtonVariant.Primary"
              .disabled="applyDraftAction.disabled ?? false"
              .focusableWhenDisabled="
                !!(applyDraftAction.disabled && applyDraftAction.disabledReason)
              "
              @click="applyDraft"
            >
              {{ applyDraftAction.label }}
            </craft-button>
            <craft-tooltip
              v-if="
                applyDraftAction.disabled && applyDraftAction.disabledReason
              "
              for="workflow-apply-draft"
            >
              {{ applyDraftAction.disabledReason }}
            </craft-tooltip>
          </template>
          <ReleasePicker
            v-if="canChooseRelease"
            :releases="payload.releaseOptions"
            :can-create="payload.canCreateRelease"
            :button-variant="ButtonVariant.Primary"
            @select="selectRelease"
            @create="createRelease"
          />
        </div>
      </WorkflowReviewPanel>
    </div>

    <section
      v-if="payload.workflow.draftReviews.length"
      class="workflow-details-panel__drafts"
    >
      <h3>{{ t('Drafts in Review') }}</h3>
      <WorkflowDraftReviews :drafts="payload.workflow.draftReviews" />
    </section>
  </div>
</template>

<style scoped>
  .workflow-details-panel,
  .workflow-details-panel__review {
    display: grid;
  }

  .workflow-details-panel {
    padding-block: var(--c-spacing-lg);
    padding-inline: var(--cp-container-padding);
  }

  .workflow-details-panel__drafts {
    display: grid;
    gap: var(--c-spacing-sm);
    padding: var(--c-spacing-md);
  }

  .workflow-details-panel__review + .workflow-details-panel__drafts {
    border-block-start: 1px solid var(--c-color-neutral-border-quiet);
  }

  .workflow-details-panel__drafts h3 {
    margin: 0;
    font-size: var(--c-text-sm);
  }
</style>
