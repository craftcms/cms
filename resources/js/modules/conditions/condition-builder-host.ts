import type {CpComponentRegistry} from '@/bootstrap/components';
import {createApp, h, shallowRef, type App} from 'vue';
import ConditionBuilder from './ConditionBuilder.vue';
import type {BuilderPayload, ConditionConfig} from './types';

type ConditionBuilderInstance = {
  validate(): Promise<boolean>;
  snapshot(): BuilderPayload;
};

export function defineConditionBuilderHost(
  components: CpComponentRegistry
): void {
  if (customElements.get('craft-condition-builder')) return;

  customElements.define(
    'craft-condition-builder',
    class extends HTMLElement {
      #app: App | null = null;
      readonly #builder = shallowRef<ConditionBuilderInstance>();
      #payload: BuilderPayload | null = null;

      async validate(): Promise<boolean> {
        return (await this.#builder.value?.validate()) ?? false;
      }

      connectedCallback(): void {
        if (this.#app) return;

        this.#payload ??= JSON.parse(this.dataset.payload!);
        this.#app = createApp({
          render: () =>
            h(ConditionBuilder, {
              ref: this.#builder,
              payload: this.#payload!,
              name: this.dataset.name,
              editable: this.dataset.editable !== '0',
              autofocus: this.dataset.autofocus === '1',
              onChange: (value: ConditionConfig) => {
                this.dispatchEvent(
                  new CustomEvent('condition-builder-change', {
                    bubbles: true,
                    detail: {value},
                  })
                );
                this.dispatchEvent(new Event('change', {bubbles: true}));
              },
              onValid: (valid: boolean) =>
                this.dispatchEvent(
                  new CustomEvent('condition-builder-valid', {
                    bubbles: true,
                    detail: {valid},
                  })
                ),
            }),
        });

        components.install(this.#app);
        this.#app.mount(this);
      }

      disconnectedCallback(): void {
        queueMicrotask(() => {
          if (this.isConnected || !this.#app) return;

          this.#payload = this.#builder.value!.snapshot();

          components.uninstall(this.#app);
          this.#app.unmount();
          this.#app = null;
        });
      }
    }
  );
}
