<script setup lang="ts">
  import {ButtonVariant, t} from '@craftcms/ui';
  import {computed, shallowRef, useId} from 'vue';
  import transitionsController from '@/actions/CraftCms/Cms/Http/Controllers/Workflows/WorkflowTransitionsController';
  import userReviewController from '@/actions/CraftCms/Cms/Http/Controllers/Workflows/UserReviewController';
  import type {ElementEditorActions} from '@/modules/elements/composables/useElementEditor';
  import {
    COMMENT_MAX_LENGTH,
    useCharacterLimit,
  } from '@/common/composables/useCharacterLimit';
  import CommentField from '@/common/components/CommentField.vue';
  import {
    useWorkflowTransition,
    type WorkflowReleaseState,
    type WorkflowIdentity,
  } from '../composables/useWorkflowTransition';

  type WorkflowReviewData = CraftCms.Cms.Workflow.Data.WorkflowReviewData;
  type ReviewDecision = 'comment' | 'approve' | 'requestChanges';

  const props = defineProps<{
    review: WorkflowReviewData;
    canReview: boolean;
    canRequestReviewAgain: boolean;
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

  const message = shallowRef('');
  const decision = shallowRef<ReviewDecision>(
    props.review.canComment ? 'comment' : 'approve'
  );
  const id = useId();
  const messageIsEmpty = computed(() => !message.value.trim());
  const {overageMessage} = useCharacterLimit(message, COMMENT_MAX_LENGTH);
  const currentRun = computed(() =>
    props.review.runs.find((run) => run.current)
  );
  const currentStage = computed(() =>
    currentRun.value?.stages.find((stage) => stage.current)
  );
  const displayedStageName = computed(
    () => currentStage.value?.name ?? t('this stage')
  );
  const selectedDecision = computed<ReviewDecision>(() =>
    props.canReview ? decision.value : 'comment'
  );
  const requiresMessage = computed(() => selectedDecision.value !== 'approve');
  const disabledReason = computed(() => {
    if (overageMessage.value) {
      return overageMessage.value;
    }

    if (!requiresMessage.value || !messageIsEmpty.value) {
      return null;
    }

    return t('Enter a comment before submitting.');
  });
  const canShowActions = computed(
    () =>
      props.canReview || props.canRequestReviewAgain || props.review.canComment
  );
  /** With no decision to make, the submit button just posts a comment. */
  const commentOnly = computed(
    () => !props.canReview && props.review.canComment
  );
  const submitLabel = computed(() =>
    commentOnly.value ? t('Comment') : t('Submit review')
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
      message.value = '';
    }
  );

  async function submitReview(): Promise<void> {
    error.value = null;
    if (disabledReason.value || processing.value) {
      return;
    }

    if (props.review.runId === null || props.review.stageUid === null) {
      staleReview();
      return;
    }

    const url =
      selectedDecision.value === 'comment'
        ? transitionsController.comment.url({
            workflowRun: props.review.runId,
            stage: props.review.stageUid,
          })
        : userReviewController[selectedDecision.value].url({
            workflowRun: props.review.runId,
            stage: props.review.stageUid,
          });
    await transition(
      url,
      selectedDecision.value === 'comment'
        ? {note: message.value}
        : {message: message.value}
    );
  }

  function staleReview(): void {
    error.value = t('This review is no longer current. Refresh and try again.');
  }

  async function requestReviewAgain(): Promise<void> {
    error.value = null;
    if (processing.value) {
      return;
    }

    if (props.review.runId === null || props.review.stageUid === null) {
      staleReview();
      return;
    }

    await transition(
      userReviewController.requestReview.url({
        workflowRun: props.review.runId,
        stage: props.review.stageUid,
      })
    );
  }
</script>

<template>
  <section v-if="canShowActions" class="workflow-user-review-actions">
    <craft-callout
      v-if="canReview"
      :title="t('Your input is needed')"
      variant="info"
      icon="user-group"
    >
      {{
        t('Choose an action for {stage}.', {
          stage: displayedStageName,
        })
      }}
    </craft-callout>

    <CommentField
      :id="`workflow-user-review-message-${id}`"
      v-model="message"
      :label="t('Review comment')"
      :disabled="processing"
      :overage-message="overageMessage"
      :submit-button="commentOnly"
      :submit-label="submitLabel"
      :submit-disabled="Boolean(disabledReason)"
      @submit="submitReview"
    />

    <fieldset v-if="canReview" class="workflow-user-review-actions__choices">
      <legend class="visually-hidden">{{ t('Review Action') }}</legend>

      <label
        v-if="review.canComment"
        class="workflow-user-review-actions__choice"
      >
        <input
          v-model="decision"
          type="radio"
          class="cp-radio"
          :name="`workflow-review-decision-${id}`"
          value="comment"
        />
        <span>
          <strong>{{ t('Comment') }}</strong>
          <small>{{ t('Submit general feedback without approving.') }}</small>
        </span>
      </label>

      <label class="workflow-user-review-actions__choice">
        <input
          v-model="decision"
          type="radio"
          class="cp-radio"
          :name="`workflow-review-decision-${id}`"
          value="approve"
        />
        <span>
          <strong>{{ t('Approve') }}</strong>
          <small>{{ t('Approve this stage of the workflow.') }}</small>
        </span>
      </label>

      <label class="workflow-user-review-actions__choice">
        <input
          v-model="decision"
          type="radio"
          class="cp-radio"
          :name="`workflow-review-decision-${id}`"
          value="requestChanges"
        />
        <span>
          <strong>{{ t('Request changes') }}</strong>
          <small>{{ t('Submit feedback that must be addressed.') }}</small>
        </span>
      </label>
    </fieldset>

    <div
      v-if="canRequestReviewAgain || !commentOnly"
      class="workflow-user-review-actions__footer"
    >
      <craft-button
        v-if="canRequestReviewAgain"
        type="button"
        :variant="ButtonVariant.Solid"
        .disabled="processing"
        @click="requestReviewAgain"
      >
        {{ t('Request review again') }}
      </craft-button>
      <span
        v-if="!commentOnly"
        :id="`workflow-user-review-submit-${id}`"
        class="workflow-user-review-actions__submit"
      >
        <craft-button
          type="button"
          :variant="ButtonVariant.Primary"
          .disabled="processing || Boolean(disabledReason)"
          focusable-when-disabled
          @click="submitReview"
        >
          {{ submitLabel }}
        </craft-button>
      </span>
    </div>
    <craft-tooltip
      v-if="disabledReason && !commentOnly"
      :for="`workflow-user-review-submit-${id}`"
    >
      {{ disabledReason }}
    </craft-tooltip>

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
  .workflow-user-review-actions {
    display: grid;
    gap: var(--c-spacing-md);
  }

  .workflow-user-review-actions__choices {
    display: grid;
    gap: var(--c-spacing-sm);
    padding: 0;
    border: 0;
    margin: 0;
  }

  .workflow-user-review-actions__choice {
    display: grid;
    min-height: var(--c-size-touch-target);
    align-items: start;
    gap: var(--c-spacing-sm);
    grid-template-columns: auto 1fr;
    cursor: pointer;
  }

  .workflow-user-review-actions__choice strong,
  .workflow-user-review-actions__choice small {
    display: block;
  }

  .workflow-user-review-actions__choice small {
    color: var(--c-color-neutral-text);
  }

  .workflow-user-review-actions__footer {
    display: flex;
    gap: var(--c-spacing-xs);
  }

  .workflow-user-review-actions__submit {
    display: inline-flex;
  }
</style>
