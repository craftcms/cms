<script setup lang="ts">
  import {ButtonVariant, t} from '@craftcms/ui';
  import {computed, shallowRef, useId} from 'vue';
  import controller from '@/actions/CraftCms/Cms/Http/Controllers/Workflows/WorkflowTransitionsController';
  import type {ElementEditorActions} from '@/modules/elements/composables/useElementEditor';
  import {commentToolbarButtons} from '@/modules/markdown-field/commentToolbarButtons';
  import {
    useWorkflowTransition,
    type WorkflowIdentity,
  } from '../composables/useWorkflowTransition';
  import '../../markdown-field/markdown-field';

  type WorkflowReviewData = CraftCms.Cms.Workflow.Data.WorkflowReviewData;

  const props = defineProps<{
    review: WorkflowReviewData;
    elementType: string;
    elementId: number | null;
    draftId: number | null;
    siteId: number | null;
  }>();

  const emit = defineEmits<{
    reviewUpdated: [
      review: WorkflowReviewData,
      editorActions: ElementEditorActions,
    ];
  }>();

  const note = shallowRef('');
  const id = useId();
  const noteId = `workflow-review-note-${id}`;
  const commentId = `workflow-review-comment-${id}`;
  const noteIsEmpty = computed(() => !note.value.trim());
  const canShowActions = computed(
    () =>
      props.review.canSubmit ||
      props.review.canComment ||
      props.review.canOverride
  );

  function identity(): WorkflowIdentity {
    return {
      elementType: props.elementType,
      elementId: props.elementId,
      draftId: props.draftId,
      siteId: props.siteId,
    };
  }

  const {error, processing, transition} = useWorkflowTransition(
    identity,
    (review, actions) => {
      emit('reviewUpdated', review, actions);
      note.value = '';
    }
  );

  async function submitForReview(): Promise<void> {
    await transition(controller.submit.url(), {note: note.value});
  }

  function onNoteKeydown(event: KeyboardEvent): void {
    if (
      event.key !== 'Enter' ||
      (!event.metaKey && !event.ctrlKey) ||
      !props.review.canSubmit ||
      processing.value
    ) {
      return;
    }

    event.preventDefault();
    void submitForReview();
  }

  async function addComment(): Promise<void> {
    if (noteIsEmpty.value || processing.value) {
      return;
    }

    const runId = props.review.runId;
    const stageUid = props.review.stageUid;
    if (runId === null || stageUid === null) {
      staleReview();
      return;
    }

    await transition(
      controller.comment.url({workflowRun: runId, stage: stageUid}),
      {
        note: note.value,
      }
    );
  }

  async function overrideApproval(): Promise<void> {
    if (processing.value) {
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

    const runId = props.review.runId;
    if (runId === null) {
      staleReview();
      return;
    }

    await transition(controller.override.url({workflowRun: runId}));
  }

  function staleReview(): void {
    error.value = t('This review is no longer current. Refresh and try again.');
  }
</script>

<template>
  <section v-if="canShowActions" class="workflow-default-actions">
    <label class="visually-hidden" :for="noteId">
      {{ t('Leave a comment') }}
    </label>
    <craft-markdown-field
      :id="noteId"
      class="markdown-field"
      :rows="3"
      :max-length="5000"
      :placeholder="t('Leave a comment')"
      sanitize-html
      show-toolbar
      .toolbarButtons="commentToolbarButtons"
      .value="note"
      :disabled="processing"
      @input="note = ($event.target as HTMLTextAreaElement).value"
      @keydown="onNoteKeydown"
    />

    <div class="workflow-default-actions__buttons">
      <craft-button
        v-if="review.canSubmit"
        type="button"
        :variant="ButtonVariant.Primary"
        :disabled="processing"
        @click="submitForReview"
      >
        {{ review.submitLabel }}
      </craft-button>

      <template v-if="review.canComment">
        <span class="workflow-default-actions__button">
          <craft-button
            :id="commentId"
            type="button"
            :variant="ButtonVariant.Solid"
            :disabled="processing || noteIsEmpty"
            focusable-when-disabled
            @click="addComment"
          >
            {{ t('Comment') }}
          </craft-button>
        </span>
        <craft-tooltip v-if="noteIsEmpty" :for="commentId">
          {{ t('Enter a comment before submitting.') }}
        </craft-tooltip>
      </template>

      <span v-if="review.canOverride" class="workflow-default-actions__button">
        <craft-button
          type="button"
          :variant="ButtonVariant.DangerPlain"
          :disabled="processing"
          @click="overrideApproval"
        >
          {{ t('Override approval') }}
        </craft-button>
      </span>
    </div>

    <craft-callout
      v-if="error"
      role="alert"
      variant="danger"
      icon="triangle-exclamation"
    >
      {{ error }}
    </craft-callout>
  </section>
</template>

<style scoped>
  .workflow-default-actions {
    display: grid;
    gap: var(--c-spacing-md);
  }

  .workflow-default-actions__buttons,
  .workflow-default-actions__button {
    display: flex;
    align-items: center;
  }

  .workflow-default-actions craft-markdown-field {
    contain: inline-size;
    display: block;
    width: 100%;
  }

  .workflow-default-actions :deep(.overtype-toolbar) {
    flex-wrap: wrap;
  }

  .workflow-default-actions__buttons {
    flex-wrap: wrap;
    justify-content: flex-end;
    gap: var(--c-spacing-xs);
  }
</style>
