import {mountFormHost} from './mountFormHost';
import type {CpComponentRegistry} from '@/bootstrap/components';
import {h} from 'vue';
import FormRenderer from './FormRenderer.vue';
import type {FormPayload} from './types';
import {scopeFormPayload} from './formScope';

export function defineTableFormHost(components: CpComponentRegistry): void {
  if (customElements.get('craft-table-form')) return;

  customElements.define(
    'craft-table-form',
    class extends HTMLElement {
      #mount: ReturnType<typeof mountFormHost> | null = null;
      ready: Promise<void> = Promise.resolve();

      connectedCallback(): void {
        if (this.#mount) return;
        let payload: FormPayload = JSON.parse(this.dataset.payload!);
        const name = this.getAttribute('name');
        const table = payload.nodes[0]?.control;
        if (name && table) {
          const path = name.replaceAll(']', '').split('[');
          payload = {
            ...scopeFormPayload({...payload, scope: table.path}, path, path),
            scope: payload.scope,
          };
        }
        this.#mount = mountFormHost(this, components, () =>
          h(FormRenderer, {payload})
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
