import {createApp, defineComponent, h, nextTick} from 'vue';
import {http} from '@inertiajs/vue3';
import {afterEach, beforeEach, describe, expect, it, vi} from 'vite-plus/test';
import {useFlashMessages} from '@/common/composables/useFlashMessages';
import WorkflowDefaultActions from './WorkflowDefaultActions.vue';
import WorkflowReviewPanel from './WorkflowReviewPanel.vue';
import WorkflowUserReviewActions from '../user-review/WorkflowUserReviewActions.vue';
import WorkflowUserReviewSummary from '../user-review/WorkflowUserReviewSummary.vue';

const requestSpy = vi.spyOn(http.getClient(), 'request');

type WorkflowReviewData = CraftCms.Cms.Workflow.Data.WorkflowReviewData;
type WorkflowRun = WorkflowReviewData['runs'][number];
type WorkflowTimelineItem = WorkflowRun['stages'][number]['events'][number];

function activityTarget(label: string): WorkflowTimelineItem['actor'] {
  return {label, url: null, deleted: false};
}

function timelineItem(
  overrides: Partial<WorkflowTimelineItem> = {}
): WorkflowTimelineItem {
  return {
    id: 'review',
    type: 'review',
    icon: 'rotate',
    description: 'updated the workflow stage',
    actor: activityTarget('Ada'),
    impersonator: null,
    decision: null,
    noteHtml: null,
    occurredAt: '2026-09-15T09:41:00+00:00',
    formattedOccurredAt: {
      date: '2026-09-15',
      dateLabel: 'Sep 15, 2026',
      time: '9:41 AM',
      full: 'September 15, 2026 at 9:41 AM UTC',
    },
    ...overrides,
  };
}

function submission(
  overrides: Partial<WorkflowRun['submission']> = {}
): WorkflowRun['submission'] {
  return {
    id: 'submission',
    type: 'submission',
    icon: 'clipboard-list-check',
    description: 'requested review',
    actor: activityTarget('Ada'),
    impersonator: null,
    decision: null,
    noteHtml: null,
    occurredAt: '2026-09-15T09:41:00+00:00',
    formattedOccurredAt: {
      date: '2026-09-15',
      dateLabel: 'Sep 15, 2026',
      time: '9:41 AM',
      full: 'September 15, 2026 at 9:41 AM UTC',
    },
    ...overrides,
  };
}

function workflowRun(overrides: Partial<WorkflowRun> = {}): WorkflowRun {
  return {
    id: 41,
    current: true,
    statusLabel: 'Awaiting approval',
    statusIndicator: 'pending',
    submission: submission(),
    stages: [
      {
        name: 'Publishers',
        current: true,
        approved: false,
        icon: 'clock',
        iconColor: 'warning',
        message: null,
        summaryComponent: 'craft:user-review-workflow-stage-summary',
        summaryProps: {
          approvalMode: 'total',
          approvalsRequired: 1,
          approvals: 0,
          remainingReviewers: [{name: 'Ada'}],
        },
        events: [],
      },
    ],
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

function requests() {
  return requestSpy.mock.calls.map(([request]) => ({
    method: request.method,
    url: request.url,
    data: JSON.parse(request.data as string),
  }));
}

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
    actionComponent: 'craft:user-review-workflow-stage-actions',
    showDefaultActions: false,
    actionProps: {canReview: true, canRequestReviewAgain: false},
    runs: [workflowRun()],
    canSubmit: false,
    canComment: true,
    canOverride: false,
    canRestart: true,
    canApply: false,
    ...overrides,
  };
}

describe('WorkflowReviewPanel', () => {
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

  function mount(
    workflowReview: WorkflowReviewData,
    components: Record<string, ReturnType<typeof defineComponent>> = {}
  ) {
    container = document.createElement('div');
    document.body.append(container);
    app = createApp({
      render: () =>
        h(WorkflowReviewPanel, {
          review: workflowReview,
          elementType: 'CraftCms\\Cms\\Entry\\Elements\\Entry',
          elementId: 12,
          draftId: 7,
          siteId: 1,
        }),
    });
    app.config.compilerOptions.isCustomElement = (tag) => tag.includes('-');
    app.component('craft:workflow-default-actions', WorkflowDefaultActions);
    app.component(
      'craft:user-review-workflow-stage-actions',
      WorkflowUserReviewActions
    );
    app.component(
      'craft:user-review-workflow-stage-summary',
      WorkflowUserReviewSummary
    );
    for (const [name, component] of Object.entries(components)) {
      app.component(name, component);
    }
    app.mount(container);
  }

  function actionButton(label: string): HTMLElement {
    return [
      ...container!.querySelectorAll<HTMLElement>(
        '.workflow-review craft-button'
      ),
    ].find((element) => element.textContent?.trim() === label)!;
  }

  async function enterNote(value: string): Promise<void> {
    const textarea = container!.querySelector<HTMLTextAreaElement>(
      'textarea[id^="workflow-review-note"]'
    )!;
    textarea.value = value;
    textarea.dispatchEvent(new Event('input', {bubbles: true}));
    await nextTick();
  }

  async function enterReviewMessage(value: string): Promise<void> {
    const textarea = container!.querySelector<HTMLTextAreaElement>(
      'textarea[id^="workflow-user-review-message"]'
    )!;
    textarea.value = value;
    textarea.dispatchEvent(new Event('input', {bubbles: true}));
    await nextTick();
  }

  async function chooseReviewDecision(value: string): Promise<void> {
    container!
      .querySelector<HTMLInputElement>(`input[type="radio"][value="${value}"]`)!
      .click();
    await nextTick();
  }

  it('lets reviewers approve, request changes, and comment with the required messages', async () => {
    const overridableReview = review({canOverride: true});
    requestSpy
      .mockResolvedValueOnce(
        response({
          message: 'Comment added.',
          workflowReview: overridableReview,
        })
      )
      .mockResolvedValueOnce(
        response({
          message: 'Approval recorded.',
          workflowReview: overridableReview,
        })
      )
      .mockResolvedValueOnce(
        response({
          message: 'Changes requested.',
          workflowReview: overridableReview,
        })
      );
    mount(overridableReview);

    expect(
      (
        container!.querySelector('craft-callout') as HTMLElement & {
          title: string;
        }
      ).title
    ).toBe('Your input is needed');
    expect(container!.textContent).toContain(
      'Choose an action for Publishers.'
    );
    expect(
      container!.querySelector('textarea[id^="workflow-review-note"]')
    ).toBeNull();
    expect(container!.textContent).toContain(
      'Submit general feedback without approving.'
    );
    expect(container!.textContent).toContain(
      'Approve this stage of the workflow.'
    );
    expect(container!.textContent).toContain(
      'Submit feedback that must be addressed.'
    );
    const submit = actionButton(
      'Submit review'
    ) as HTMLElementTagNameMap['craft-button'];
    await submit.updateComplete;
    expect(submit.disabled).toBe(true);
    expect(submit.tabIndex).toBe(0);
    expect(container!.querySelector('craft-tooltip')?.textContent).toContain(
      'Enter a comment before submitting.'
    );

    await enterReviewMessage('Please clarify this section.');
    expect(submit.disabled).toBe(false);
    container!
      .querySelector<HTMLTextAreaElement>(
        'textarea[id^="workflow-user-review-message"]'
      )!
      .dispatchEvent(
        new KeyboardEvent('keydown', {
          key: 'Enter',
          ctrlKey: true,
          bubbles: true,
          composed: true,
        })
      );
    await vi.waitFor(() =>
      expect(useFlashMessages().messages.value.success).toBe('Comment added.')
    );

    await chooseReviewDecision('approve');
    expect(submit.disabled).toBe(false);
    submit.click();
    await vi.waitFor(() =>
      expect(useFlashMessages().messages.value.success).toBe(
        'Approval recorded.'
      )
    );

    await chooseReviewDecision('requestChanges');
    await enterReviewMessage('The legal claim needs support.');
    submit.click();
    await vi.waitFor(() =>
      expect(useFlashMessages().messages.value.success).toBe(
        'Changes requested.'
      )
    );

    expect(requests()).toEqual([
      expect.objectContaining({
        method: 'post',
        url: '/admin/workflows/41/stages/publishers-stage/comment',
        data: expect.objectContaining({
          note: 'Please clarify this section.',
        }),
      }),
      expect.objectContaining({
        method: 'post',
        url: '/admin/workflows/41/stages/publishers-stage/user-review/approve',
      }),
      expect.objectContaining({
        method: 'post',
        url: '/admin/workflows/41/stages/publishers-stage/user-review/request-changes',
        data: expect.objectContaining({
          message: 'The legal claim needs support.',
        }),
      }),
    ]);
  });

  it('presents approval progress for each reviewer group', () => {
    mount(
      review({
        runs: [
          workflowRun({
            stages: [
              {
                ...workflowRun().stages[0]!,
                summaryProps: {
                  approvalMode: 'per-group',
                  approvalsRequired: 2,
                  groups: [
                    {
                      name: 'Editorial',
                      approvals: 2,
                      carriedReviewers: [{name: 'Ada'}],
                      remainingReviewers: [],
                    },
                    {
                      name: 'Legal',
                      approvals: 1,
                      carriedReviewers: [{name: 'Ada'}],
                      remainingReviewers: [{name: 'Lin'}],
                    },
                  ],
                },
              },
            ],
          }),
        ],
      })
    );

    expect(container!.textContent).toContain('Editorial');
    expect(container!.textContent).toContain('2 of 2 approved');
    expect(container!.textContent).toContain('Legal');
    expect(container!.textContent).toContain('1 of 2 approved');
    expect(container!.textContent).toContain('Lin');
    expect(container!.textContent).toContain('Approved previously');
  });

  it('omits review choices when commenting is the only available action', () => {
    mount(
      review({
        actionProps: {canReview: false, canRequestReviewAgain: false},
        canComment: true,
        canOverride: false,
      })
    );

    expect(container!.textContent).not.toContain('Approve this stage');
    expect(container!.textContent).not.toContain('Request changes');
    expect(actionButton('Comment')).toBeTruthy();
  });

  it('requests another review after changes were requested', async () => {
    const changesRequested = review({
      actionProps: {canReview: false, canRequestReviewAgain: true},
    });
    requestSpy.mockResolvedValueOnce(
      response({message: 'Review requested.', workflowReview: review()})
    );
    mount(changesRequested);

    actionButton('Request review again').click();
    await vi.waitFor(() => expect(requests()).toHaveLength(1));

    expect(requests()[0]).toEqual(
      expect.objectContaining({
        method: 'post',
        url: '/admin/workflows/41/stages/publishers-stage/user-review/request-review',
      })
    );
  });

  it('comments after a partial approval removes the reviewers remaining actions', async () => {
    const partialApproval = review({
      actionProps: {canReview: false, canRequestReviewAgain: false},
      canComment: true,
      runs: [
        workflowRun({
          stages: [
            {
              ...workflowRun().stages[0]!,
              summaryProps: {
                approvalMode: 'total',
                approvalsRequired: 2,
                approvals: 1,
                remainingReviewers: [{name: 'Grace'}],
              },
            },
          ],
        }),
      ],
    });
    requestSpy
      .mockResolvedValueOnce(
        response({
          message: 'Approval recorded.',
          workflowReview: partialApproval,
        })
      )
      .mockResolvedValueOnce(
        response({
          message: 'Comment added.',
          workflowReview: partialApproval,
        })
      );
    mount(review());

    await chooseReviewDecision('approve');
    actionButton('Submit review').click();
    await vi.waitFor(() => expect(actionButton('Comment')).toBeTruthy());
    await enterReviewMessage('A follow-up note.');
    actionButton('Comment').click();
    await vi.waitFor(() => expect(requests()).toHaveLength(2));

    expect(requests()[1]).toEqual(
      expect.objectContaining({
        url: '/admin/workflows/41/stages/publishers-stage/comment',
        data: expect.objectContaining({note: 'A follow-up note.'}),
      })
    );
  });

  it('lets plugin stages compose the default actions into their UI', () => {
    const PluginStage = defineComponent({
      props: {prompt: {type: String, required: true}},
      inheritAttrs: false,
      template: `
        <div>
          <button type="button">{{ prompt }}</button>
          <component :is="'craft:workflow-default-actions'" v-bind="$attrs" />
        </div>
      `,
    });
    mount(
      review({
        actionComponent: 'plugin:compliance-stage',
        showDefaultActions: false,
        actionProps: {prompt: 'Attest compliance'},
      }),
      {'plugin:compliance-stage': PluginStage}
    );

    expect(container!.textContent).toContain('Attest compliance');
    expect(actionButton('Comment')).toBeTruthy();
  });

  it('lets the requester comment without offering review decisions', async () => {
    mount(
      review({
        actionComponent: null,
        showDefaultActions: true,
        actionProps: {canReview: false},
      })
    );

    expect(container!.textContent).not.toContain('Approve');
    expect(container!.textContent).not.toContain('Request changes');
    await (actionButton('Comment') as HTMLElementTagNameMap['craft-button'])
      .updateComplete;
    expect(
      (actionButton('Comment') as HTMLElementTagNameMap['craft-button'])
        .disabled
    ).toBe(true);
    expect(actionButton('Comment').tabIndex).toBe(0);

    await enterNote('One more detail for the reviewers.');
    expect(
      (actionButton('Comment') as HTMLElementTagNameMap['craft-button'])
        .disabled
    ).toBe(false);
  });

  it('shows the workflow message returned by a failed transition', async () => {
    requestSpy.mockResolvedValue(
      response(
        {
          message:
            'Every stage needs enough eligible reviewers to meet its approval threshold.',
        },
        422
      )
    );
    mount(
      review({
        status: 'notSubmitted',
        statusLabel: 'Not submitted',
        statusIndicator: 'disabled',
        applyDisabledReason: 'Submit this draft for review before applying it.',
        submitLabel: 'Submit for review',
        runId: null,
        currentStage: null,
        stageUid: null,
        actionComponent: null,
        showDefaultActions: true,
        actionProps: {canReview: false},
        runs: [],
        canSubmit: true,
        canRestart: false,
      })
    );

    const textarea = container!.querySelector<HTMLTextAreaElement>(
      'textarea[id^="workflow-review-note"]'
    )!;
    textarea.dispatchEvent(
      new KeyboardEvent('keydown', {
        key: 'Enter',
        metaKey: true,
        bubbles: true,
      })
    );
    await vi.waitFor(() =>
      expect(container!.textContent).toContain(
        'Every stage needs enough eligible reviewers to meet its approval threshold.'
      )
    );
    expect(container!.querySelector('[role="alert"]')).not.toBeNull();
  });

  it('shows the review request, decisions, and comments as timeline items', () => {
    mount(
      review({
        actionComponent: null,
        showDefaultActions: true,
        actionProps: {canReview: false},
        runs: [
          workflowRun({
            id: 42,
            submission: submission({
              id: 'second-submission',
              actor: activityTarget('Rias'),
              noteHtml: '<p>Ready for review.</p>',
            }),
            stages: [],
          }),
          workflowRun({
            id: 41,
            current: false,
            statusLabel: 'Changes requested',
            statusIndicator: 'expired',
            submission: submission({
              id: 'first-submission',
              actor: activityTarget('Rias'),
            }),
            stages: [
              {
                name: 'Editorial',
                current: false,
                approved: false,
                icon: 'xmark',
                iconColor: 'danger',
                message: 'Changes requested',
                summaryComponent: 'craft:user-review-workflow-stage-summary',
                summaryProps: {
                  approvalMode: 'total',
                  approvalsRequired: 2,
                  approvals: 1,
                  remainingReviewers: [{name: 'Grace'}, {name: 'Lin'}],
                },
                events: [
                  timelineItem({
                    id: 'approval',
                    icon: 'check',
                    description: 'approved',
                    actor: activityTarget('Ada'),
                    decision: 'approved',
                    noteHtml: '<p>Looks good.</p>',
                  }),
                  timelineItem({
                    id: 'change-request',
                    icon: 'xmark',
                    description: 'requested changes',
                    actor: activityTarget('Grace'),
                    decision: 'rejected',
                    noteHtml: '<p>Please revise this.</p>',
                  }),
                  timelineItem({
                    id: 'comment',
                    type: 'comment',
                    icon: 'comment',
                    description: 'commented.',
                    actor: activityTarget('Lin'),
                    decision: null,
                    noteHtml: '<p>Can you clarify this?</p>',
                  }),
                  timelineItem({
                    id: 'automated-failure',
                    icon: 'xmark',
                    description: 'failed the stage',
                    actor: activityTarget('Craft CMS'),
                    decision: 'failed',
                    noteHtml: '<p>Unsupported claims were found.</p>',
                  }),
                ],
              },
            ],
          }),
        ],
      })
    );

    const text = container!.textContent?.replace(/\s+/g, ' ');
    expect(text).toContain('Review 2');
    expect(text).toContain('Review 1');
    expect(text).toContain('Rias requested review');
    expect(text).toContain('Ready for review.');
    expect(text).toContain('Ada approved');
    expect(text).toContain('Looks good.');
    expect(text).toContain('Grace requested changes');
    expect(text).toContain('Please revise this.');
    expect(text).toContain('Lin commented.');
    expect(text).toContain('Can you clarify this?');
    expect(text).toContain('Craft CMS failed the stage');
    expect(text).toContain('Unsupported claims were found.');
  });

  it('shows each stage summary and expands the current stage', async () => {
    mount(
      review({
        runs: [
          workflowRun({
            stages: [
              {
                name: 'Editorial',
                current: false,
                approved: true,
                icon: 'check',
                iconColor: 'success',
                message: null,
                summaryComponent: 'craft:user-review-workflow-stage-summary',
                summaryProps: {
                  approvalMode: 'total',
                  approvalsRequired: 1,
                  approvals: 1,
                  approvedReviewers: [{name: 'Ada'}],
                  remainingReviewers: [],
                },
                events: [
                  timelineItem({
                    icon: 'check',
                    description: 'approved',
                    decision: 'approved',
                  }),
                ],
              },
              {
                name: 'Legal',
                current: true,
                approved: false,
                icon: 'clock',
                iconColor: 'warning',
                message: null,
                summaryComponent: 'craft:user-review-workflow-stage-summary',
                summaryProps: {
                  approvalMode: 'total',
                  approvalsRequired: 2,
                  approvals: 0,
                  remainingReviewers: [{name: 'Grace'}, {name: 'Lin'}],
                },
                events: [],
              },
              {
                name: 'Automated policy check',
                current: false,
                approved: false,
                icon: 'minus',
                iconColor: 'neutral',
                message: 'Waiting for the external policy service.',
                summaryComponent: null,
                summaryProps: {},
                events: [],
              },
            ],
          }),
        ],
      })
    );
    await nextTick();

    const disclosures = [...container!.querySelectorAll('craft-disclosure')];
    const editorial = disclosures.find((item) =>
      item.textContent?.includes('Editorial')
    )!;
    const legal = disclosures.find((item) =>
      item.textContent?.includes('Legal')
    )!;
    const automatedCheck = disclosures.find((item) =>
      item.textContent?.includes('Automated policy check')
    )!;

    expect(editorial.opened).toBe(false);
    expect(editorial.textContent).toContain('Ada');
    expect(editorial.textContent).toContain('Approved');
    expect(legal.opened).toBe(true);
    expect(legal.textContent).toContain('Grace');
    expect(legal.textContent).toContain('Lin');
    expect(legal.textContent).toContain('0 of 2 approved');
    expect(automatedCheck.textContent).toContain(
      'Waiting for the external policy service.'
    );
  });

  it('renders stages without details as static status rows', async () => {
    mount(
      review({
        runs: [
          workflowRun({
            stages: [
              workflowRun().stages[0]!,
              {
                name: 'External review',
                current: false,
                approved: true,
                icon: 'check',
                iconColor: 'success',
                message: null,
                summaryComponent: null,
                summaryProps: {},
                events: [],
              },
            ],
          }),
        ],
      })
    );
    await nextTick();

    const externalReview = [
      ...container!.querySelectorAll('.workflow-review-stage'),
    ].find((stage) => stage.textContent?.includes('External review'))!;

    expect(externalReview.tagName).toBe('SECTION');
    expect(externalReview.querySelector('craft-button')).toBeNull();
    expect(externalReview.querySelector('[name="chevron-down"]')).toBeNull();
  });
});
