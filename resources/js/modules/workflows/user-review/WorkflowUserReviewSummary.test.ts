import {createApp, h} from 'vue';
import {afterEach, expect, it} from 'vite-plus/test';
import WorkflowUserReviewSummary from './WorkflowUserReviewSummary.vue';

type WorkflowReviewData = CraftCms.Cms.Workflow.Data.WorkflowReviewData;
type WorkflowRun = WorkflowReviewData['runs'][number];

let app: ReturnType<typeof createApp> | undefined;
let container: HTMLElement | undefined;

afterEach(() => {
  app?.unmount();
  container?.remove();
});

function badges(): Record<string, string> {
  return Object.fromEntries(
    [
      ...container!.querySelectorAll(
        '.workflow-user-review-summary__reviewer-row'
      ),
    ].map((row) => [
      row
        .querySelector('.workflow-user-review-summary__name')!
        .textContent!.trim(),
      row.querySelector('craft-badge')!.textContent!.trim(),
    ])
  );
}

it.each<['total' | 'per-group', Record<string, unknown>]>([
  [
    'total',
    {
      approvals: 1,
      approvedReviewers: [{name: 'Helena Vogt'}],
      carriedReviewers: [],
      requiredReviewers: [{name: 'Jonas Aaltonen'}],
      remainingReviewers: [{name: 'Amara Okafor'}],
    },
  ],
  [
    'per-group',
    {
      groups: [
        {
          name: 'Curators',
          approvals: 1,
          approvedReviewers: [{name: 'Helena Vogt'}],
          carriedReviewers: [],
          requiredReviewers: [{name: 'Jonas Aaltonen'}],
          remainingReviewers: [{name: 'Amara Okafor'}],
        },
      ],
    },
  ],
])(
  'marks reviewers who must approve their own edits (%s)',
  (approvalMode, props) => {
    container = document.createElement('div');
    document.body.append(container);
    app = createApp({
      render: () =>
        h(WorkflowUserReviewSummary, {
          run: {} as WorkflowRun,
          stage: {} as WorkflowRun['stages'][number],
          approvalMode,
          approvalsRequired: 1,
          ...props,
        }),
    });
    app.config.compilerOptions.isCustomElement = (tag) => tag.includes('-');
    app.mount(container);

    expect(badges()).toEqual({
      'Helena Vogt': 'Approved',
      'Jonas Aaltonen': 'Approval required',
      'Amara Okafor': 'Pending',
    });
  }
);
