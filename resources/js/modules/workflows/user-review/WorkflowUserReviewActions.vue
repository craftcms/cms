<script setup lang="ts">
  import {ButtonVariant, t} from '@craftcms/ui';
  import {HttpResponseError} from '@inertiajs/core';
  import {useHttp} from '@inertiajs/vue3';
  import {computed, shallowRef, useId} from 'vue';
  import transitionsController from '@/actions/CraftCms/Cms/Http/Controllers/Workflows/WorkflowTransitionsController';
  import userReviewController from '@/actions/CraftCms/Cms/Http/Controllers/Workflows/UserReviewController';
  import {useFlashMessages} from '@/common/composables/useFlashMessages';
  import type {ElementEditorActions} from '@/modules/elements/composables/useElementEditor';
  import {commentToolbarButtons} from '@/modules/markdown-field/commentToolbarButtons';
  import {isModifierKeyPressed} from '@/modules/markdown-field/behaviors/utilities';
  import '@/modules/markdown-field/markdown-field';

  type WorkflowReviewData = CraftCms.Cms.Workflow.Data.WorkflowReviewData;
  type WorkflowIdentity = {
    elementType: string;
    elementId: number | null;
    draftId: number | null;
    siteId: number | null;
  };
  type ReviewDecision = 'comment' | 'approve' | 'requestChanges';
  type WorkflowActionRequest = WorkflowIdentity & {
    message?: string;
    note?: string;
  };
  type WorkflowResponse = {
    workflowReview: WorkflowReviewData;
    editorActions: ElementEditorActions;
    message?: string;
  };

  const props = defineProps<{
    review: WorkflowReviewData;
    canReview: boolean;
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

  const message = shallowRef('');
  const decision = shallowRef<ReviewDecision>(
    props.review.canComment ? 'comment' : 'approve'
  );
  const error = shallowRef<string | null>(null);
  const {flash} = useFlashMessages();
  const actionRequest = useHttp<WorkflowActionRequest, WorkflowResponse>({
    ...identity(),
  });
  const id = useId();
  const messageIsEmpty = computed(() => !message.value.trim());
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
    if (!requiresMessage.value || !messageIsEmpty.value) {
      return null;
    }

    return t('Enter a comment before submitting.');
  });
  const canShowActions = computed(
    () => props.canReview || props.review.canComment || props.review.canOverride
  );
  const submitLabel = computed(() =>
    !props.canReview && props.review.canComment
      ? t('Comment')
      : t('Submit review')
  );

  function identity(): WorkflowIdentity {
    return {
      elementType: props.elementType,
      elementId: props.elementId,
      draftId: props.draftId,
      siteId: props.siteId,
    };
  }

  async function submitReview(): Promise<void> {
    error.value = null;
    if (disabledReason.value || actionRequest.processing) {
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

  function onMessageKeydown(event: KeyboardEvent): void {
    if (event.key !== 'Enter' || !isModifierKeyPressed(event)) {
      return;
    }

    event.preventDefault();
    void submitReview();
  }

  function staleReview(): void {
    error.value = t('This review is no longer current. Refresh and try again.');
  }

  async function overrideApproval(): Promise<void> {
    error.value = null;
    if (actionRequest.processing) {
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

    if (props.review.runId === null) {
      staleReview();
      return;
    }

    await transition(
      transitionsController.override.url({workflowRun: props.review.runId}),
      {note: message.value}
    );
  }

  async function transition(
    url: string,
    values: Partial<Pick<WorkflowActionRequest, 'message' | 'note'>>
  ): Promise<void> {
    actionRequest.transform(() => ({...identity(), ...values}));

    try {
      const data = await actionRequest.post(url);
      emit('reviewUpdated', data.workflowReview, data.editorActions);
      message.value = '';
      if (data.message) {
        flash('success', data.message);
      }
    } catch (exception) {
      error.value =
        responseMessage(exception) ??
        (exception instanceof Error
          ? exception.message
          : t('The workflow could not be updated.'));
    }
  }

  function responseMessage(exception: unknown): string | null {
    if (!(exception instanceof HttpResponseError)) {
      return null;
    }

    try {
      const response = JSON.parse(exception.response.data) as {
        message?: unknown;
      };

      return typeof response.message === 'string' ? response.message : null;
    } catch {
      return null;
    }
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

    <label class="visually-hidden" :for="`workflow-user-review-message-${id}`">
      {{ t('Review comment') }}
    </label>
    <craft-markdown-field
      :id="`workflow-user-review-message-${id}`"
      class="markdown-field"
      :rows="3"
      :max-length="5000"
      :placeholder="t('Review comment')"
      sanitize-html
      show-toolbar
      .toolbarButtons="commentToolbarButtons"
      .value="message"
      :disabled="actionRequest.processing"
      @input="message = ($event.target as HTMLTextAreaElement).value"
      @keydown="onMessageKeydown"
    />

    <fieldset v-if="canReview" class="workflow-user-review-actions__choices">
      <legend class="visually-hidden">{{ t('Review action') }}</legend>

      <label
        v-if="review.canComment"
        class="workflow-user-review-actions__choice"
      >
        <input
          v-model="decision"
          type="radio"
          :name="`workflow-review-decision-${id}`"
          value="comment"
        />
        <span>
          <strong>{{ t('Comment') }}</strong>
          <small>{{ t('Submit general feedback without approving.') }}</small>
        </span>
      </label>

      <label v-if="canReview" class="workflow-user-review-actions__choice">
        <input
          v-model="decision"
          type="radio"
          :name="`workflow-review-decision-${id}`"
          value="approve"
        />
        <span>
          <strong>{{ t('Approve') }}</strong>
          <small>{{ t('Approve this stage of the workflow.') }}</small>
        </span>
      </label>

      <label v-if="canReview" class="workflow-user-review-actions__choice">
        <input
          v-model="decision"
          type="radio"
          :name="`workflow-review-decision-${id}`"
          value="requestChanges"
        />
        <span>
          <strong>{{ t('Request changes') }}</strong>
          <small>{{ t('Submit feedback that must be addressed.') }}</small>
        </span>
      </label>
    </fieldset>

    <div class="workflow-user-review-actions__footer">
      <craft-button
        v-if="review.canOverride"
        type="button"
        :variant="ButtonVariant.DangerPlain"
        .disabled="actionRequest.processing"
        @click="overrideApproval"
      >
        {{ t('Override approval') }}
      </craft-button>
      <span
        :id="`workflow-user-review-submit-${id}`"
        class="workflow-user-review-actions__submit"
      >
        <craft-button
          type="button"
          :variant="ButtonVariant.Primary"
          .disabled="actionRequest.processing || Boolean(disabledReason)"
          focusable-when-disabled
          @click="submitReview"
        >
          {{ submitLabel }}
        </craft-button>
      </span>
      <craft-tooltip
        v-if="disabledReason"
        :for="`workflow-user-review-submit-${id}`"
      >
        {{ disabledReason }}
      </craft-tooltip>
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
  .workflow-user-review-actions {
    display: grid;
    gap: var(--c-spacing-md);
  }

  .workflow-user-review-actions craft-markdown-field {
    contain: inline-size;
    display: block;
    width: 100%;
  }

  .workflow-user-review-actions :deep(.overtype-toolbar) {
    flex-wrap: wrap;
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

  .workflow-user-review-actions__choice input {
    margin-block-start: 0.2em;
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
    justify-content: flex-end;
    gap: var(--c-spacing-xs);
  }

  .workflow-user-review-actions__submit {
    display: inline-flex;
  }
</style>
