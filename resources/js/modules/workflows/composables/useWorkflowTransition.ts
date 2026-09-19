import {t} from '@craftcms/ui';
import {HttpResponseError} from '@inertiajs/core';
import {useHttp} from '@inertiajs/vue3';
import {computed, shallowRef, type Ref} from 'vue';
import {useFlashMessages} from '@/common/composables/useFlashMessages';
import type {ElementEditorActions} from '@/modules/elements/composables/useElementEditor';

type WorkflowReviewData = CraftCms.Cms.Workflow.Data.WorkflowReviewData;

export type WorkflowIdentity = {
  elementType: string;
  elementId: number | null;
  draftId: number | null;
  siteId: number | null;
};

type WorkflowTransitionResponse = {
  workflowReview: WorkflowReviewData;
  editorActions: ElementEditorActions;
  message?: string;
};

type WorkflowTransitionRequest = WorkflowIdentity & {
  message?: string;
  note?: string;
};

export function useWorkflowTransition(
  identity: () => WorkflowIdentity,
  updated: (review: WorkflowReviewData, actions: ElementEditorActions) => void
): {
  error: Ref<string | null>;
  processing: Ref<boolean>;
  transition: (
    url: string,
    values?: Pick<WorkflowTransitionRequest, 'message' | 'note'>
  ) => Promise<void>;
} {
  const error = shallowRef<string | null>(null);
  const {flash} = useFlashMessages();
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
      updated(data.workflowReview, data.editorActions);
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
