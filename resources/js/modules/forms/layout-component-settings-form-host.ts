import type {CpComponentRegistry} from '@/bootstrap/components';
import {createApp, defineComponent, h, ref, shallowRef, type App} from 'vue';
import FormRenderer from './FormRenderer.vue';
import type {FormPayload} from './types';

// TODO: Remove this legacy bridge once the field layout designer's settings
// slideout is rendered by the Inertia/Vue CP.

type FormErrors = Record<string, string | string[]>;

type CraftRuntime = typeof Craft & {
  appendHeadHtml(html: string): Promise<void>;
  appendBodyHtml(html: string): Promise<void>;
};
type FormRendererInstance = {
  currentValues(): FormPayload['values'];
};

/** The layout component this settings form is for, as posted to the server. */
export type LayoutComponentRequestData = {
  uid: string;
  elementType: string;
  layoutConfig: unknown;
  config?: unknown;
};

export function defineLayoutComponentSettingsFormHost(
  components: CpComponentRegistry
): void {
  if (customElements.get('craft-layout-component-settings-form')) {
    return;
  }

  customElements.define(
    'craft-layout-component-settings-form',
    class extends HTMLElement {
      readonly #payload = shallowRef<FormPayload | null>(null);
      readonly #errors = shallowRef<FormPayload['errors']>([]);
      readonly #renderer = ref<FormRendererInstance | null>(null);
      #app: App | null = null;
      #requestData: (() => LayoutComponentRequestData) | null = null;

      set payload(payload: FormPayload | null) {
        this.#payload.value = payload;
        this.#errors.value = payload?.errors ?? [];
      }

      get payload(): FormPayload | null {
        return this.#payload.value;
      }

      set requestData(requestData: () => LayoutComponentRequestData) {
        this.#requestData = requestData;
      }

      set errors(errors: FormErrors) {
        const scope = this.#payload.value?.scope ?? [];
        this.#errors.value = Object.entries(errors).map(([path, messages]) => ({
          path: [...scope, ...path.split('.')],
          messages: Array.isArray(messages) ? messages : [messages],
        }));
      }

      connectedCallback(): void {
        if (this.#app) {
          return;
        }

        this.#app = createApp(
          defineComponent({
            setup: () => {
              return () =>
                this.#payload.value
                  ? h(FormRenderer, {
                      ref: this.#renderer,
                      payload: this.#payload.value,
                      errors: this.#errors.value,
                      refresh: this.#payload.value.refreshable
                        ? this.#refresh.bind(this)
                        : undefined,
                    })
                  : null;
            },
          })
        );
        this.#app.config.compilerOptions.isCustomElement = (tag) =>
          tag.includes('-');
        components.install(this.#app);
        this.#app.mount(this);
      }

      disconnectedCallback(): void {
        if (this.#app) {
          components.uninstall(this.#app);
          this.#app.unmount();
        }
        this.#app = null;
      }

      /** The settings values, relative to the component (not the form scope). */
      currentValues(): Record<string, unknown> {
        const values = this.#renderer.value?.currentValues() ?? {};

        return (values.settings ?? {}) as Record<string, unknown>;
      }

      async #refresh(
        values: FormPayload['values'],
        scope: string[] = this.#payload.value?.scope ?? []
      ): Promise<FormPayload> {
        if (!this.#requestData) {
          throw new Error(
            'Layout component request data is required to refresh its settings.'
          );
        }

        const {data} = await Craft.sendActionRequest(
          'POST',
          'fields/refresh-layout-component-settings',
          {
            data: {
              // `values` is already relative to `scope`, unlike currentValues().
              ...this.#requestData(),
              settings: values,
              scope,
            },
          }
        );

        if (!data.form) {
          throw new Error(
            'The layout component did not return a Form payload.'
          );
        }

        // Server-rendered controls (condition builders, field selects) register
        // their own assets on every render.
        const craft = Craft as CraftRuntime;
        await craft.appendHeadHtml(data.headHtml);
        await craft.appendBodyHtml(data.bodyHtml);

        return data.form;
      }
    }
  );
}
