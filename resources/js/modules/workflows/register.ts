import type {CpComponentRegistry} from '@/bootstrap/components';
import type {ElementDetailsPanelRegistry} from '@/bootstrap/element-details-panels';
import {t} from '@craftcms/ui';
import WorkflowDefaultActions from './components/WorkflowDefaultActions.vue';
import WorkflowDetailsActions from './components/WorkflowDetailsActions.vue';
import WorkflowDetailsPanel from './components/WorkflowDetailsPanel.vue';
import WorkflowUserReviewActions from './user-review/WorkflowUserReviewActions.vue';
import WorkflowUserReviewSummary from './user-review/WorkflowUserReviewSummary.vue';

export function registerWorkflowComponents(
  components: Pick<CpComponentRegistry, 'register'>,
  elementDetailsPanels: Pick<ElementDetailsPanelRegistry, 'register'>
): void {
  components.register('craft:workflow-default-actions', WorkflowDefaultActions);
  components.register(
    'craft:user-review-workflow-stage-actions',
    WorkflowUserReviewActions
  );
  components.register(
    'craft:user-review-workflow-stage-summary',
    WorkflowUserReviewSummary
  );
  components.register(
    'craft:workflow-activity-event',
    () => import('./components/WorkflowActivityTimelineEvent.vue')
  );
  elementDetailsPanels.register({
    id: 'workflow',
    get label() {
      return t('Workflow');
    },
    icon: 'clipboard-list-check',
    component: WorkflowDetailsPanel,
    headerActionsComponent: WorkflowDetailsActions,
    order: 5,
    visible: (payload) =>
      Boolean(payload.workflow.current || payload.workflow.draftReviews.length),
    status: (payload) =>
      payload.workflow.current
        ? {
            label: payload.workflow.current.statusLabel,
            indicator: payload.workflow.current.statusIndicator,
          }
        : null,
    props: ({payload, updatePayload, submitAction}) => ({
      payload,
      updatePayload,
      submitAction,
    }),
  });
}
