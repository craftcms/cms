import {t} from '@craftcms/ui';
import {HttpResponseError} from '@inertiajs/core';
import {useHttp} from '@inertiajs/vue3';
import {computed, shallowRef, type Ref} from 'vue';
import {useMessages} from '@/modules/messages/useMessages';
import type {
  ElementEditorActions,
  ElementEditPayload,
} from '@/modules/elements/composables/useElementEditor';

type WorkflowReviewData = CraftCms.Cms.Workflow.Data.WorkflowReviewData;

export type WorkflowIdentity = {
  elementType: string;
  elementId: number | null;
  draftId: number | null;
  siteId: number | null;
};

export type WorkflowReleaseState = Pick<
  ElementEditPayload,
  'canAddToRelease' | 'canCreateRelease' | 'releaseOptions'
>;

type WorkflowTransitionResponse = {
  workflowReview: WorkflowReviewData;
  editorActions: ElementEditorActions;
  release?: WorkflowReleaseState | null;
  message?: string;
};

type WorkflowTransitionRequest = WorkflowIdentity & {
  message?: string;
  note?: string;
};

export function useWorkflowTransition(
  identity: () => WorkflowIdentity,
  updated: (
    review: WorkflowReviewData,
    actions: ElementEditorActions,
    release: WorkflowReleaseState | null | undefined
  ) => void
): {
  error: Ref<string | null>;
  processing: Ref<boolean>;
  transition: (
    url: string,
    values?: Pick<WorkflowTransitionRequest, 'message' | 'note'>
  ) => Promise<void>;
} {
  const error = shallowRef<string | null>(null);
  const messages = useMessages();
  const request = useHttp<
    WorkflowTransitionRequest,
    WorkflowTransitionResponse
  >(identity());

  async function transition(
    url: string,
    values: Pick<WorkflowTransitionRequest, 'message' | 'note'> = {}
  ): Promise<void> {
    error.value = null;
    request.transform(() => ({...identity(), ...values}));

    try {
      const data = await request.post(url);
      updated(data.workflowReview, data.editorActions, data.release);
      if (data.message) {
        messages.success(data.message);
      }
    } catch (exception) {
      error.value =
        responseMessage(exception) ??
        (exception instanceof Error
          ? exception.message
          : t('The workflow could not be updated.'));
    }
  }

  return {error, processing: computed(() => request.processing), transition};
}

function responseMessage(exception: unknown): string | null {
  if (!(exception instanceof HttpResponseError)) {
    return null;
  }

  try {
    const response = JSON.parse(exception.response.data) as {message?: unknown};

    return typeof response.message === 'string' ? response.message : null;
  } catch {
    return null;
  }
}
