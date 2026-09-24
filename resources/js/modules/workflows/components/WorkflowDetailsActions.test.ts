import {http} from '@inertiajs/vue3';
import {createApp, h, nextTick, shallowRef} from 'vue';
import {afterEach, beforeEach, describe, expect, it, vi} from 'vite-plus/test';
import {useFlashMessages} from '@/common/composables/useFlashMessages';
import type {
  ElementEditPayload,
  ElementEditPayloadUpdater,
} from '@/modules/elements/composables/useElementEditor';
import WorkflowDetailsActions from './WorkflowDetailsActions.vue';

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
    runId: 41,
    currentStage: 1,
    stageUid: 'publishers-stage',
    actionComponent: null,
    showDefaultActions: true,
    actionProps: {},
    runs: [],
    canSubmit: false,
    canComment: true,
    canOverride: true,
    canRestart: true,
    canApply: false,
    ...overrides,
  };
}

function response(data: unknown, status = 200) {
  return {
    status,
    headers: {},
    data: JSON.stringify(data),
  };
}

describe('WorkflowDetailsActions', () => {
  let app: ReturnType<typeof createApp> | undefined;
  let container: HTMLElement | undefined;

  beforeEach(() => {
    requestSpy.mockReset();
    useFlashMessages().clearAll();
    vi.stubGlobal(
      'confirm',
      vi.fn(() => true)
    );
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

  function mount(workflowReview: WorkflowReviewData): void {
    const payload = shallowRef({
      elementType: 'CraftCms\\Cms\\Entry\\Elements\\Entry',
      canonicalId: 12,
      draftId: 7,
      siteId: 1,
      workflow: {
        current: workflowReview,
        draftReviews: [],
      },
      editorActions: {
        primary: {
          label: 'Save',
          actionUrl: null,
          params: {},
          redirect: null,
          tabId: null,
        },
        menu: [],
        buttons: [],
      },
    } as unknown as ElementEditPayload);
    const updatePayload: ElementEditPayloadUpdater = (update) => {
      const patch =
        typeof update === 'function' ? update(payload.value) : update;
      payload.value = {...payload.value, ...patch};
    };

    container = document.createElement('div');
    document.body.append(container);
    app = createApp({
      render: () =>
        h(WorkflowDetailsActions, {
          payload: payload.value,
          updatePayload,
        }),
    });
    app.config.compilerOptions.isCustomElement = (tag) => tag.includes('-');
    app.mount(container);
  }

  function action(label: string): HTMLElement {
    return [
      ...container!.querySelectorAll<HTMLElement>('craft-action-item'),
    ].find((item) => item.textContent?.trim() === label)!;
  }

  it('offers override and restart from the header menu', async () => {
    const workflowReview = review();
    requestSpy
      .mockResolvedValueOnce(
        response({
          message: 'Workflow approved.',
          workflowReview,
          editorActions: {},
        })
      )
      .mockResolvedValueOnce(
        response({
          message: 'Workflow restarted.',
          workflowReview,
          editorActions: {},
        })
      );
    mount(workflowReview);
    await nextTick();

    expect(action('Override approval')).toBeTruthy();
    expect(action('Restart workflow')).toBeTruthy();

    vi.mocked(window.confirm).mockReturnValueOnce(false);
    action('Override approval').click();
    await nextTick();
    expect(requestSpy).not.toHaveBeenCalled();

    action('Override approval').click();
    await vi.waitFor(() => expect(requestSpy).toHaveBeenCalledTimes(1));
    action('Restart workflow').click();
    await vi.waitFor(() => expect(requestSpy).toHaveBeenCalledTimes(2));

    expect(window.confirm).toHaveBeenCalledWith(
      'This will bypass the remaining workflow requirements and approve the draft. Are you sure?'
    );
    expect(window.confirm).toHaveBeenCalledWith(
      'This will discard all approvals and restart the workflow from its first stage. Are you sure?'
    );
    expect(requestSpy.mock.calls.map(([request]) => request.url)).toEqual([
      '/admin/workflows/41/override',
      '/admin/workflows/41/restart',
    ]);
  });

  it('hides the menu when there are no available workflow actions', () => {
    mount(review({canOverride: false, canRestart: false}));

    expect(container!.querySelector('craft-action-menu')).toBeNull();
  });
});
