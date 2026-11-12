import {mountUiHost} from './mountUiHost';
import type {CpComponentRegistry} from '@/bootstrap/components';
import {h, nextTick} from 'vue';
import UiRenderer from './UiRenderer.vue';
import type {UiPayload} from './types';
import {scopeUiPayload} from './uiScope';

type Renderer = {rebase(values: UiPayload['values']): void};

export function defineTableUiHost(components: CpComponentRegistry): void {
  if (customElements.get('craft-table-ui')) return;

  customElements.define(
    'craft-table-ui',
    class extends HTMLElement {
      #mount: ReturnType<typeof mountUiHost> | null = null;
      #renderer: Renderer | null = null;
      ready: Promise<void> = Promise.resolve();

      connectedCallback(): void {
        if (this.#mount) return;
        const payload = this.#scoped(JSON.parse(this.dataset.payload!));
        this.#mount = mountUiHost(this, components, () =>
          h(UiRenderer, {
            payload,
            ref: (renderer) => {
              this.#renderer = renderer as Renderer | null;
            },
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

      /**
       * Takes the values in `payload`, the table as the server rendered it
       * after a save, as the ones it now holds, leaving the values the table
       * is showing as they are.
       */
      rebase(payload: UiPayload): void {
        this.#renderer?.rebase(this.#scoped(payload).values);
      }

      #scoped(payload: UiPayload): UiPayload {
        const name = this.getAttribute('name');
        const table = payload.nodes[0]?.control;
        if (!name || !table) return payload;
        const path = name.replaceAll(']', '').split('[');

        return {
          ...scopeUiPayload({...payload, scope: table.path}, path, path),
          scope: payload.scope,
        };
      }
    }
  );
}
