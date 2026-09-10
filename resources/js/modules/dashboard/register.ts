import type {CpComponentRegistry} from '@/bootstrap/components';

export function registerWidgetComponents(
  components: Pick<CpComponentRegistry, 'register'>
): void {
  components.register('craft:html-widget', () => import('./HtmlWidget.vue'));
  components.register('craft:widget-feed', () => import('./Feed.vue'));
  components.register('craft:widget-new-users', () => import('./NewUsers.vue'));
  components.register(
    'craft:widget-craft-support',
    () => import('./craft-support/Index.vue')
  );
  components.register(
    'craft:widget-quick-post',
    () => import('./QuickPost.vue')
  );
  components.register(
    'craft:widget-recent-entries',
    () => import('./RecentEntries.vue')
  );
  components.register('craft:widget-my-drafts', () => import('./MyDrafts.vue'));
  components.register('craft:widget-updates', () => import('./Updates.vue'));
}
