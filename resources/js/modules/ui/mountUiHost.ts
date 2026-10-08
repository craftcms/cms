import type {CpComponentRegistry} from '@/bootstrap/components';
import {createApp, nextTick, type VNode} from 'vue';

export function mountUiHost(
  host: HTMLElement,
  components: CpComponentRegistry,
  render: () => VNode
) {
  const app = createApp({setup: () => render});
  app.config.compilerOptions.isCustomElement = (tag) => tag.includes('-');
  components.install(app);
  app.mount(host);

  return {
    ready: nextTick().then(async () => {
      for (const element of host.querySelectorAll('*')) {
        if ('updateComplete' in element) await element.updateComplete;
        if ('ready' in element) await element.ready;
      }
    }),
    unmount(): void {
      components.uninstall(app);
      app.unmount();
    },
  };
}
