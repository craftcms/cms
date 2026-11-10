import type {CpComponentRegistry} from '@/bootstrap/components';
import type {ElementDetailsPanelRegistry} from '@/bootstrap/element-details-panels';
import {t} from '@craftcms/ui';
import {defineAsyncComponent} from 'vue';

/**
 * Loaded once a page shows a workflow, so the comment and release pickers they
 * render stay off every other page. Module-level, so registering twice hands
 * the registries the same components.
 */
const WorkflowDefaultActions = () =>
  import('./components/WorkflowDefaultActions.vue');
const WorkflowUserReviewActions = () =>
  import('./user-review/WorkflowUserReviewActions.vue');
const WorkflowUserReviewSummary = () =>
  import('./user-review/WorkflowUserReviewSummary.vue');
const WorkflowActivityEvent = () =>
  import('./components/WorkflowActivityTimelineEvent.vue');
const WorkflowDetailsPanel = defineAsyncComponent(
  () => import('./components/WorkflowDetailsPanel.vue')
);
const WorkflowDetailsActions = defineAsyncComponent(
  () => import('./components/WorkflowDetailsActions.vue')
);

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
  components.register('craft:workflow-activity-event', WorkflowActivityEvent);
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
      payload.workflow.current &&
      payload.workflow.current.status !== 'withdrawn'
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
