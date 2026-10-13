import {mountUiHost} from './mountUiHost';
import type {CpComponentRegistry} from '@/bootstrap/components';
import {h, nextTick} from 'vue';
import UiRenderer from './UiRenderer.vue';
import type {UiPayload} from './types';
import {scopeUiPayload} from './uiScope';

export function defineTableUiHost(components: CpComponentRegistry): void {
  if (customElements.get('craft-table-ui')) return;

  customElements.define(
    'craft-table-ui',
    class extends HTMLElement {
      #mount: ReturnType<typeof mountUiHost> | null = null;
      ready: Promise<void> = Promise.resolve();

      connectedCallback(): void {
        if (this.#mount) return;
        let payload: UiPayload = JSON.parse(this.dataset.payload!);
        const name = this.getAttribute('name');
        const table = payload.nodes[0]?.control;
        if (name && table) {
          const path = name.replaceAll(']', '').split('[');
          payload = {
            ...scopeUiPayload({...payload, scope: table.path}, path, path),
            scope: payload.scope,
          };
        }
        this.#mount = mountUiHost(this, components, () =>
          h(UiRenderer, {
            payload,
            onChange: () => {
              void nextTick().then(() => {
                if (this.isConnected) {
                  this.dispatchEvent(new Event('change', {bubbles: true}));
                }
              });
            },
          })
        );
        this.ready = this.#mount.ready;
      }

      disconnectedCallback(): void {
        if (!this.#mount) return;
        this.#mount.unmount();
        this.#mount = null;
      }
    }
  );
}
