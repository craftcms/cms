import {mountFormHost} from './mountFormHost';
import type {CpComponentRegistry} from '@/bootstrap/components';
import {h, shallowRef} from 'vue';
import FormRenderer from './FormRenderer.vue';
import type {FormPayload} from './types';
import {firstFocusableWithin} from '@/common/utils/dom';

export interface InlineAttributeFormHost extends HTMLElement {
  ready: Promise<void>;
  errors: Record<string, string[]>;
  canSubmit(): boolean;
  focusFirst(): boolean;
}

/** Mounts Form controls in the legacy table without changing its row save contract. */
export function defineInlineAttributeFormHost(
  components: CpComponentRegistry
): void {
  if (customElements.get('craft-inline-attribute-form')) {
    return;
  }

  customElements.define(
    'craft-inline-attribute-form',
    class extends HTMLElement {
      #mount: ReturnType<typeof mountFormHost> | null = null;
      #payload: FormPayload | null = null;
      readonly #errors = shallowRef<FormPayload['errors']>([]);
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

        const payload: FormPayload = JSON.parse(this.dataset.payload!);
        this.#payload = payload;
        this.#errors.value = payload.errors;
        this.#mount = mountFormHost(this, components, () =>
          h(FormRenderer, {
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
