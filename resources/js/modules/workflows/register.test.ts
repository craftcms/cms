import {createApp} from 'vue';
import {describe, expect, it} from 'vite-plus/test';
import {createCpComponentRegistry} from '@/bootstrap/components';
import {createElementDetailsTabRegistry} from '@/bootstrap/element-details-tabs';
import {registerWorkflowComponents} from './register';

describe('workflow registration', () => {
  it('registers the workflow details tab for reviews and drafts in review', () => {
    const app = createApp({render: () => null});
    const components = createCpComponentRegistry();
    const tabs = createElementDetailsTabRegistry();

    components.install(app);
    registerWorkflowComponents(components, tabs);

    expect(app.component('craft:workflow-default-actions')).toBeTruthy();
    expect(
      app.component('craft:user-review-workflow-stage-actions')
    ).toBeTruthy();
    expect(
      app.component('craft:user-review-workflow-stage-summary')
    ).toBeTruthy();
    expect(app.component('craft:workflow-activity-event')).toBeTruthy();

    const tab = tabs.tabs[0]!;
    const payload = {
      workflow: {
        current: null,
        draftReviews: [],
      },
    } as unknown as Parameters<NonNullable<typeof tab.visible>>[0];

    expect(tab).toMatchObject({
      id: 'workflow',
      icon: 'clipboard-list-check',
      order: 5,
    });
    expect(tab.visible!(payload)).toBe(false);
    expect(tab.status!(payload)).toBeNull();
    expect(
      tab.status!({
        ...payload,
        workflow: {
          ...payload.workflow,
          current: {
            statusLabel: 'Awaiting approval',
            statusIndicator: 'pending',
          } as CraftCms.Cms.Workflow.Data.WorkflowReviewData,
        },
      })
    ).toEqual({label: 'Awaiting approval', indicator: 'pending'});
    expect(
      tab.visible!({
        ...payload,
        workflow: {
          ...payload.workflow,
          current: {} as CraftCms.Cms.Workflow.Data.WorkflowReviewData,
        },
      })
    ).toBe(true);
    expect(
      tab.visible!({
        ...payload,
        workflow: {
          ...payload.workflow,
          draftReviews: [
            {} as CraftCms.Cms.Workflow.Data.WorkflowDraftReviewData,
          ],
        },
      })
    ).toBe(true);
  });
});
