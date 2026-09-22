import type {CpComponentRegistry} from '@/bootstrap/components';
import {
  createApp,
  defineComponent,
  h,
  nextTick,
  shallowRef,
  type App,
} from 'vue';
import FormRenderer from './FormRenderer.vue';
import type {FormPayload} from './types';

export interface InlineAttributeFormHost extends HTMLElement {
  ready: Promise<void>;
  errors: Record<string, string[]>;
  canSubmit(): boolean;
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
      #app: App | null = null;
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
        if (this.#app) {
          return;
        }

        const payload: FormPayload = JSON.parse(this.dataset.payload!);
        this.#payload = payload;
        this.#errors.value = payload.errors;
        this.#app = createApp(
          defineComponent({
            setup: () => () =>
              h(FormRenderer, {
                payload,
                ref: this.#renderer,
                errors: this.#errors.value,
              }),
          })
        );
        this.#app.config.compilerOptions.isCustomElement = (tag) =>
          tag.includes('-');
        components.install(this.#app);
        this.#app.mount(this);
        this.ready = this.#whenReady();
      }

      async #whenReady(): Promise<void> {
        await nextTick();

        for (const element of this.querySelectorAll('*')) {
          if ('updateComplete' in element) {
            await element.updateComplete;
          }

          if ('ready' in element) {
            await element.ready;
          }
        }
      }

      canSubmit(): boolean {
        return this.#renderer.value?.canSubmit() ?? false;
      }

      disconnectedCallback(): void {
        if (this.#app) {
          components.uninstall(this.#app);
          this.#app.unmount();
          this.#app = null;
        }
      }
    }
  );
}
