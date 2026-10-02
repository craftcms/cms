import type {CpComponentRegistry} from '@/bootstrap/components';

export function registerActivityComponents(
  components: Pick<CpComponentRegistry, 'register'>
): void {
  components.register(
    'craft:activity-timeline-event',
    () => import('./components/ActivityTimelineEvent.vue')
  );
  components.register(
    'craft:activity-timeline-comment',
    () => import('./components/ActivityTimelineComment.vue')
  );
}
