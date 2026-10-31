import {http} from '@inertiajs/vue3';
import {createApp, h, nextTick, shallowRef, type ShallowRef} from 'vue';
import {afterEach, beforeEach, describe, expect, it, vi} from 'vite-plus/test';
import type {
  ElementEditPayload,
  ElementEditPayloadUpdater,
} from '@/modules/elements/composables/useElementEditor';
import WorkflowEditLockCallout from './WorkflowEditLockCallout.vue';

const messages = vi.hoisted(() => ({
  showMessage: vi.fn(),
  notice: vi.fn(),
  success: vi.fn(),
  error: vi.fn(),
}));
vi.mock('@/modules/messages/useMessages', () => ({
  useMessages: () => messages,
}));

type WorkflowReviewData = CraftCms.Cms.Workflow.Data.WorkflowReviewData;

const requestSpy = vi.spyOn(http.getClient(), 'request');

function review(
  overrides: Partial<WorkflowReviewData> = {}
): WorkflowReviewData {
  return {
    status: 'pending',
    statusLabel: 'Awaiting approval',
    statusIndicator: 'pending',
    submitLabel: 'Submit for review',
    applyDisabledReason: null,
    runId: 41,
    currentStage: 1,
    stageUid: 'final-stage',
    actionComponent: null,
    showDefaultActions: true,
    actionProps: {},
    runs: [],
    canSubmit: false,
    canComment: true,
    canOverride: false,
    canRestart: true,
    canApply: false,
    isReviewer: false,
    hasApproved: false,
    rollbackStage: null,
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

describe('WorkflowEditLockCallout', () => {
  let app: ReturnType<typeof createApp> | undefined;
  let container: HTMLElement | undefined;
  let payload: ShallowRef<ElementEditPayload>;
  let startEditing: () => void;

  beforeEach(() => {
    requestSpy.mockReset();
    Object.values(messages).forEach((fn) => fn.mockReset());
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
    payload = shallowRef({
      elementType: 'CraftCms\\Cms\\Entry\\Elements\\Entry',
      canonicalId: 12,
      draftId: 7,
      siteId: 1,
      workflow: {
        current: workflowReview,
        draftReviews: [],
      },
    } as unknown as ElementEditPayload);
    const updatePayload: ElementEditPayloadUpdater = (update) => {
      const patch =
        typeof update === 'function' ? update(payload.value) : update;
      payload.value = {...payload.value, ...patch};
    };
    startEditing = vi.fn<() => void>();

    container = document.createElement('div');
    document.body.append(container);
    app = createApp({
      render: () =>
        h(WorkflowEditLockCallout, {
          payload: payload.value,
          updatePayload,
          onStartEditing: startEditing,
        }),
    });
    app.config.compilerOptions.isCustomElement = (tag) => tag.includes('-');
    app.mount(container);
  }

  function callout(): HTMLElement & {variant?: string} {
    return container!.querySelector('craft-callout')!;
  }

  function startEditingButton(): HTMLElement {
    return container!.querySelector('craft-button')!;
  }

  it.each([
    [
      'withdraws a draft for someone outside its review',
      review(),
      'warning',
      'Editing this draft will withdraw it from review.',
    ],
    [
      'sends a draft back to the earlier stage the user reviews',
      review({rollbackStage: 'Copy review'}),
      'warning',
      'Editing this draft will send it back to the “Copy review” stage.',
    ],
    [
      'reopens an approved draft for its final-stage reviewers',
      review({status: 'approved', isReviewer: true, hasApproved: true}),
      'info',
      'This draft is approved and ready to be published.',
    ],
    [
      'lets a reviewer edit a draft they approved',
      review({isReviewer: true, hasApproved: true}),
      'info',
      'You’ve approved this draft.',
    ],
  ])('%s', (_, workflowReview, variant, text) => {
    mount(workflowReview);

    expect(callout().variant).toBe(variant);
    expect(callout().textContent).toContain(text);
    expect(startEditingButton().textContent?.trim()).toBe('Start editing');
  });

  it('takes the draft out of review before unlocking it', async () => {
    const withdrawn = review({status: 'withdrawn', canSubmit: true});
    requestSpy.mockResolvedValueOnce(
      response({workflowReview: withdrawn, editorActions: {}})
    );
    mount(review());

    startEditingButton().click();

    await vi.waitFor(() => expect(startEditing).toHaveBeenCalledOnce());
    expect(requestSpy.mock.calls.map(([request]) => request.url)).toEqual([
      '/admin/workflows/41/prepare-for-editing',
    ]);
    expect(payload.value.workflow.current).toEqual(withdrawn);
  });

  it('stays locked when the review changed in the meantime', async () => {
    requestSpy.mockResolvedValueOnce(
      response(
        {message: 'This review is no longer current. Refresh and try again.'},
        400
      )
    );
    mount(review());

    startEditingButton().click();

    await vi.waitFor(() =>
      expect(messages.error).toHaveBeenCalledWith(
        'This review is no longer current. Refresh and try again.'
      )
    );
    expect(startEditing).not.toHaveBeenCalled();
  });

  it('unlocks a reviewer’s draft without changing its review', async () => {
    mount(review({isReviewer: true, hasApproved: true}));

    startEditingButton().click();
    await nextTick();

    expect(startEditing).toHaveBeenCalledOnce();
    expect(requestSpy).not.toHaveBeenCalled();
  });
});
