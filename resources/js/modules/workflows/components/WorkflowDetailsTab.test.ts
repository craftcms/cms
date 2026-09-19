import {createApp, h, nextTick, shallowRef} from 'vue';
import {http} from '@inertiajs/vue3';
import {afterEach, beforeEach, describe, expect, it, vi} from 'vite-plus/test';
import type {
  ElementEditPayload,
  ElementEditPayloadUpdater,
} from '@/modules/elements/composables/useElementEditor';
import {elementFormActionSubmitterKey} from '@/modules/elements/composables/useElementEditor';
import WorkflowUserReviewActions from '../user-review/WorkflowUserReviewActions.vue';
import WorkflowDetailsTab from './WorkflowDetailsTab.vue';

type WorkflowReviewData = CraftCms.Cms.Workflow.Data.WorkflowReviewData;

const requestSpy = vi.spyOn(http.getClient(), 'request');

function review(
  overrides: Partial<WorkflowReviewData> = {}
): WorkflowReviewData {
  return {
    status: 'pending',
    statusLabel: 'Awaiting approval',
    statusIndicator: 'pending',
    submitLabel: 'Request new review',
    applyDisabledReason:
      'This draft must be approved before it can be applied.',
    runId: 40,
    currentStage: 0,
    stageUid: 'publishers-stage',
    actionComponent: 'craft:user-review-workflow-stage-actions',
    showDefaultActions: false,
    actionProps: {canReview: true},
    runs: [],
    canSubmit: false,
    canComment: false,
    canOverride: false,
    canApply: false,
    ...overrides,
  };
}

describe('WorkflowDetailsTab', () => {
  let app: ReturnType<typeof createApp> | undefined;
  let container: HTMLElement | undefined;

  beforeEach(() => {
    requestSpy.mockReset();
    vi.stubGlobal(
      'fetch',
      vi.fn(async () => new Response('<svg></svg>'))
    );
  });

  afterEach(() => {
    app?.unmount();
    container?.remove();
    vi.unstubAllGlobals();
  });

  it('enables applying a draft after its review is approved', async () => {
    const approvedReview = review({
      status: 'approved',
      statusLabel: 'Approved',
      statusIndicator: 'enabled',
      applyDisabledReason: null,
      runId: 41,
      currentStage: 1,
      actionComponent: null,
      showDefaultActions: true,
      actionProps: {},
      canApply: true,
    });
    const approvedEditorActions = {
      primary: {
        label: 'Save draft',
        actionUrl: '/actions/elements/save-draft',
        params: {},
        redirect: null,
        tabId: null,
      },
      menu: [],
      buttons: [
        {
          label: 'Apply draft',
          actionUrl: '/actions/elements/apply-draft',
          params: {workflowRunId: 41, workflowCurrentStage: 1},
          redirect: null,
          disabled: false,
          disabledReason: null,
          includeFormData: false,
        },
      ],
    };
    const pendingEditorActions = {
      ...approvedEditorActions,
      buttons: [
        {
          ...approvedEditorActions.buttons[0],
          params: {workflowRunId: 40, workflowCurrentStage: 0},
          disabled: true,
          disabledReason: 'Approval is required.',
          includeFormData: true,
        },
      ],
    };
    requestSpy.mockResolvedValue({
      status: 200,
      headers: {},
      data: JSON.stringify({
        workflowReview: approvedReview,
        editorActions: approvedEditorActions,
      }),
    });

    const elementPayload = shallowRef({
      workflow: {
        current: review(),
        draftReviews: [],
      },
      applyDraftUrl: '/actions/elements/apply-draft',
      draftId: 7,
      isProvisionalDraft: false,
      elementType: 'craft\\elements\\Entry',
      canonicalId: 12,
      siteId: 1,
      editorActions: pendingEditorActions,
    } as unknown as ElementEditPayload);
    const updatePayload: ElementEditPayloadUpdater = (update) => {
      const patch =
        typeof update === 'function' ? update(elementPayload.value) : update;
      elementPayload.value = {...elementPayload.value, ...patch};
    };
    const submitAction = vi.fn();

    container = document.createElement('div');
    document.body.append(container);
    app = createApp({
      render: () =>
        h(WorkflowDetailsTab, {
          payload: elementPayload.value,
          updatePayload,
        }),
    });
    app.config.compilerOptions.isCustomElement = (tag) => tag.includes('-');
    app.component(
      'craft:user-review-workflow-stage-actions',
      WorkflowUserReviewActions
    );
    app.provide(elementFormActionSubmitterKey, submitAction);
    app.mount(container);

    const submitReview = [
      ...container.querySelectorAll<HTMLElement>('craft-button'),
    ].find((button) => button.textContent?.trim() === 'Submit review')!;
    submitReview.click();

    await vi.waitFor(() =>
      expect(elementPayload.value.workflow.current).toEqual(approvedReview)
    );
    await nextTick();

    const applyDraft = [
      ...container.querySelectorAll<HTMLElement>('craft-button'),
    ].find((button) => button.textContent?.trim() === 'Apply draft')!;
    expect(applyDraft).toBeTruthy();
    applyDraft.click();
    expect(submitAction).toHaveBeenCalledWith(approvedEditorActions.buttons[0]);
  });
});
