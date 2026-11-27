import type {CpComponentRegistry} from '@/bootstrap/components';
import {createApp, nextTick, type VNode} from 'vue';
import {UiFieldIdScope} from './runtime';

/**
 * @param idScope A prefix for the ids of the fields rendered within, for a host
 *   that can share the page with another rendering the same fields. See
 *   {@link UiFieldIdScope}.
 */
export function mountUiHost(
  host: HTMLElement,
  components: CpComponentRegistry,
  render: () => VNode,
  idScope?: string
) {
  const app = createApp({setup: () => render});
  if (idScope) {
    app.provide(UiFieldIdScope, idScope);
  }
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
