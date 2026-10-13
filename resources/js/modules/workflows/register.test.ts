import {createApp} from 'vue';
import {describe, expect, it} from 'vite-plus/test';
import {createCpComponentRegistry} from '@/bootstrap/components';
import {createElementDetailsPanelRegistry} from '@/bootstrap/element-details-panels';
import {registerWorkflowComponents} from './register';

describe('workflow registration', () => {
  it('registers the workflow details panel for reviews and drafts in review', () => {
    const app = createApp({render: () => null});
    const components = createCpComponentRegistry();
    const panels = createElementDetailsPanelRegistry();

    components.install(app);
    registerWorkflowComponents(components, panels);

    expect(app.component('craft:workflow-default-actions')).toBeTruthy();
    expect(
      app.component('craft:user-review-workflow-stage-actions')
    ).toBeTruthy();
    expect(
      app.component('craft:user-review-workflow-stage-summary')
    ).toBeTruthy();
    expect(app.component('craft:workflow-activity-event')).toBeTruthy();

    const panel = panels.panels[0]!;
    const payload = {
      workflow: {
        current: null,
        draftReviews: [],
      },
    } as unknown as Parameters<NonNullable<typeof panel.visible>>[0];

    expect(panel).toMatchObject({
      id: 'workflow',
      icon: 'clipboard-list-check',
      order: 5,
    });
    expect(panel.visible!(payload)).toBe(false);
    expect(panel.status!(payload)).toBeNull();
    expect(
      panel.status!({
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
      panel.visible!({
        ...payload,
        workflow: {
          ...payload.workflow,
          current: {} as CraftCms.Cms.Workflow.Data.WorkflowReviewData,
        },
      })
    ).toBe(true);
    expect(
      panel.visible!({
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
