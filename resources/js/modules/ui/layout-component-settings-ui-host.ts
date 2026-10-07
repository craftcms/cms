import type {CpComponentRegistry} from '@/bootstrap/components';
import {appendBodyHtml, appendHeadHtml} from '@craftcms/ui';
import {createApp, defineComponent, h, ref, shallowRef, type App} from 'vue';
import UiRenderer from './UiRenderer.vue';
import {isRecord} from './runtime';
import type {UiPayload, UiPropertyValue, UiValues} from './types';

// TODO: Remove this legacy bridge once the field layout designer's settings
// slideout is rendered by the Inertia/Vue CP.

type UiErrors = Record<string, string | string[]>;

type UiRendererInstance = {
  currentValues(): UiPayload['values'];
};

/** The layout component this settings form is for, as posted to the server. */
export type LayoutComponentRequestData = {
  uid: string;
  elementType: string;
  layoutConfig: UiPropertyValue;
  config?: UiPropertyValue;
};

export function defineLayoutComponentSettingsUiHost(
  components: CpComponentRegistry
): void {
  if (customElements.get('craft-layout-component-settings-ui')) {
    return;
  }

  customElements.define(
    'craft-layout-component-settings-ui',
    class extends HTMLElement {
      readonly #payload = shallowRef<UiPayload | null>(null);
      readonly #errors = shallowRef<UiPayload['errors']>([]);
      readonly #renderer = ref<UiRendererInstance | null>(null);
      #app: App | null = null;
      #requestData: (() => LayoutComponentRequestData) | null = null;

      set payload(payload: UiPayload | null) {
        this.#payload.value = payload;
        this.#errors.value = payload?.errors ?? [];
      }

      get payload(): UiPayload | null {
        return this.#payload.value;
      }

      set requestData(requestData: () => LayoutComponentRequestData) {
        this.#requestData = requestData;
      }

      set errors(errors: UiErrors) {
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
                  ? h(UiRenderer, {
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
      currentValues(): UiValues {
        const values = this.#renderer.value?.currentValues() ?? {};
        const settings = values.settings;

        return isRecord(settings) ? settings : {};
      }

      async #refresh(
        values: UiPayload['values'],
        scope: string[] = this.#payload.value?.scope ?? []
      ): Promise<UiPayload> {
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
          throw new Error('The layout component did not return a UI payload.');
        }

        // Server-rendered controls (condition builders, field selects) register
        // their own assets on every render.
        await appendHeadHtml(data.headHtml);
        await appendBodyHtml(data.bodyHtml);

        return data.form;
      }
    }
  );
}
