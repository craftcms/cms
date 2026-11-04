<script setup lang="ts">
  import {ButtonVariant, t} from '@craftcms/ui';
  import {computed, shallowRef, useId} from 'vue';
  import controller from '@/actions/CraftCms/Cms/Http/Controllers/Workflows/WorkflowTransitionsController';
  import type {ElementEditorActions} from '@/modules/elements/composables/useElementEditor';
  import CommentField from '@/common/components/CommentField.vue';
  import {
    useWorkflowTransition,
    type WorkflowReleaseState,
    type WorkflowIdentity,
  } from '../composables/useWorkflowTransition';
  import {
    COMMENT_MAX_LENGTH,
    useCharacterLimit,
  } from '@/common/composables/useCharacterLimit';

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
      release: WorkflowReleaseState | null | undefined,
    ];
  }>();

  const note = shallowRef('');
  const id = useId();
  const noteId = `workflow-review-note-${id}`;
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
    (review, actions, release) => {
      emit('reviewUpdated', review, actions, release);
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
   * Return posts the comment that's been written. With nothing written,
   * Ctrl/Command + Return submits for review instead, where the note is
   * optional.
   */
  function onNoteSubmit(event: KeyboardEvent | MouseEvent): void {
    if (processing.value || overage.value > 0) {
      return;
    }

    if (props.review.canComment && !noteIsEmpty.value) {
      void addComment();
    } else if (props.review.canSubmit && (event.metaKey || event.ctrlKey)) {
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
    <CommentField
      :id="noteId"
      v-model="note"
      :label="t('Leave a comment')"
      :disabled="processing"
      :overage-message="overageMessage"
      :submit-button="review.canComment"
      :submit-label="t('Comment')"
      :submit-disabled="overage > 0"
      @submit="onNoteSubmit"
    />

    <div v-if="review.canSubmit" class="workflow-default-actions__buttons">
      <craft-button
        type="button"
        :variant="ButtonVariant.Primary"
        .disabled="processing || overage > 0"
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

  .workflow-default-actions__buttons {
    flex-wrap: wrap;
    justify-content: flex-end;
    gap: var(--c-spacing-xs);
  }
</style>
