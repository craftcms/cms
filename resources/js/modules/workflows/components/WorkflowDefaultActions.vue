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
  import {
    COMMENT_MAX_LENGTH,
    useCharacterLimit,
  } from '@/common/composables/useCharacterLimit';
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
  const {overage, overageMessage} = useCharacterLimit(note, COMMENT_MAX_LENGTH);
  const canShowActions = computed(
    () => props.review.canSubmit || props.review.canComment
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
    if (overage.value > 0) {
      return;
    }

    await transition(controller.submit.url(), {note: note.value});
  }

  /**
   * Ctrl/Command + Enter posts the comment that's been written. With nothing
   * written, it submits for review instead, where the note is optional.
   */
  function onNoteKeydown(event: KeyboardEvent): void {
    if (
      event.key !== 'Enter' ||
      (!event.metaKey && !event.ctrlKey) ||
      event.isComposing ||
      processing.value ||
      overage.value > 0
    ) {
      return;
    }

    if (props.review.canComment && !noteIsEmpty.value) {
      event.preventDefault();
      void addComment();
    } else if (props.review.canSubmit) {
      event.preventDefault();
      void submitForReview();
    }
  }

  async function addComment(): Promise<void> {
    if (noteIsEmpty.value || processing.value || overage.value > 0) {
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

  function staleReview(): void {
    error.value = t('This review is no longer current. Refresh and try again.');
  }
</script>

<template>
  <section v-if="canShowActions" class="workflow-default-actions">
    <label class="visually-hidden" :for="noteId">
      {{ t('Leave a comment') }}
    </label>
    <div
      class="workflow-default-actions__field"
      :class="{
        'workflow-default-actions__field--footer':
          review.canComment || overageMessage,
      }"
    >
      <craft-markdown-field
        :id="noteId"
        class="markdown-field"
        :max-height="200"
        :rows="1"
        :placeholder="t('Leave a comment')"
        sanitize-html
        show-toolbar
        .toolbarButtons="commentToolbarButtons"
        .value="note"
        :disabled="processing"
        @input="note = ($event.target as HTMLTextAreaElement).value"
        @keydown="onNoteKeydown"
      />

      <div class="workflow-default-actions__field-footer">
        <span class="workflow-default-actions__overage" aria-live="polite">
          {{ overageMessage }}
        </span>
        <span
          v-if="review.canComment"
          class="workflow-default-actions__comment"
        >
          <craft-button
            :id="commentId"
            type="button"
            size="small"
            :variant="ButtonVariant.Solid"
            :disabled="processing || noteIsEmpty || overage > 0"
            focusable-when-disabled
            @click="addComment"
          >
            {{ t('Comment') }}
          </craft-button>
        </span>
      </div>
      <craft-tooltip v-if="review.canComment && noteIsEmpty" :for="commentId">
        {{ t('Enter a comment before submitting.') }}
      </craft-tooltip>
    </div>

    <div v-if="review.canSubmit" class="workflow-default-actions__buttons">
      <craft-button
        type="button"
        :variant="ButtonVariant.Primary"
        :disabled="processing || overage > 0"
        @click="submitForReview"
      >
        {{ review.submitLabel }}
      </craft-button>
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

  .workflow-default-actions__buttons {
    display: flex;
    align-items: center;
  }

  .workflow-default-actions__field {
    position: relative;
  }

  .workflow-default-actions__field--footer craft-markdown-field {
    --markdown-field-footer-height: calc(
      var(--c-size-control-sm) + var(--c-spacing-sm) * 2
    );
  }

  .workflow-default-actions__field-footer {
    position: absolute;
    inset-block-end: var(--c-spacing-sm);
    inset-inline-end: var(--c-spacing-sm);
    display: flex;
    align-items: center;
    gap: var(--c-spacing-md);
  }

  .workflow-default-actions__overage {
    color: var(--c-color-danger-on-quiet);
    font-size: var(--c-text-sm);
  }

  .workflow-default-actions__comment {
    display: flex;
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
