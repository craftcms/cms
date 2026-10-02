import type {CpComponentRegistry} from '@/bootstrap/components';
import NoticeNode from './NoticeNode.vue';
import SlugControl from './SlugControl.vue';

export function registerTestPluginFormComponents(
  components: Pick<CpComponentRegistry, 'register'>
): void {
  components.register('test-plugin:notice', NoticeNode);
  components.register('test-plugin:slug', SlugControl);
}
