import type {CpComponentRegistry} from '@/bootstrap/components';
import type {ElementDetailsTabRegistry} from '@/bootstrap/element-details-tabs';
import {t} from '@craftcms/ui';
import WorkflowDefaultActions from './components/WorkflowDefaultActions.vue';
import WorkflowDetailsActions from './components/WorkflowDetailsActions.vue';
import WorkflowDetailsTab from './components/WorkflowDetailsTab.vue';
import WorkflowUserReviewActions from './user-review/WorkflowUserReviewActions.vue';
import WorkflowUserReviewSummary from './user-review/WorkflowUserReviewSummary.vue';

export function registerWorkflowComponents(
  components: Pick<CpComponentRegistry, 'register'>,
  elementDetailsTabs: Pick<ElementDetailsTabRegistry, 'register'>
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
  elementDetailsTabs.register({
    id: 'workflow',
    get label() {
      return t('Workflow');
    },
    icon: 'clipboard-list-check',
    component: WorkflowDetailsTab,
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
