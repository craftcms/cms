import {mountUiHost} from './mountUiHost';
import type {CpComponentRegistry} from '@/bootstrap/components';
import {h, shallowRef} from 'vue';
import UiRenderer from './UiRenderer.vue';
import type {UiPayload} from './types';
import {firstFocusableWithin} from '@/common/utils/dom';

export interface InlineAttributeUiHost extends HTMLElement {
  ready: Promise<void>;
  errors: Record<string, string[]>;
  canSubmit(): boolean;
  focusFirst(): boolean;
}

/** Mounts UI controls in the legacy table without changing its row save contract. */
export function defineInlineAttributeUiHost(
  components: CpComponentRegistry
): void {
  if (customElements.get('craft-inline-attribute-ui')) {
    return;
  }

  customElements.define(
    'craft-inline-attribute-ui',
    class extends HTMLElement {
      #mount: ReturnType<typeof mountUiHost> | null = null;
      #payload: UiPayload | null = null;
      readonly #errors = shallowRef<UiPayload['errors']>([]);
      readonly #renderer = shallowRef<{canSubmit(): boolean} | null>(null);
      ready: Promise<void> = Promise.resolve();

      set errors(errors: Record<string, string[]>) {
        const scope = this.#payload?.scope ?? [];
        this.#errors.value = Object.entries(errors).map(
          ([attribute, messages]) => ({
            path: [...scope, ...attribute.replace(/^field:/, '').split('.')],
            messages,
          })
        );
      }

      connectedCallback(): void {
        if (this.#mount) {
          return;
        }

        const payload: UiPayload = JSON.parse(this.dataset.payload!);
        this.#payload = payload;
        this.#errors.value = payload.errors;
        this.#mount = mountUiHost(this, components, () =>
          h(UiRenderer, {
            payload,
            ref: this.#renderer,
            errors: this.#errors.value,
          })
        );
        this.ready = this.#mount.ready;
      }

      canSubmit(): boolean {
        return this.#renderer.value?.canSubmit() ?? false;
      }

      focusFirst(): boolean {
        const target = firstFocusableWithin(this);
        target?.focus();

        return Boolean(target);
      }

      disconnectedCallback(): void {
        if (this.#mount) {
          this.#mount.unmount();
          this.#mount = null;
        }
      }
    }
  );
}
