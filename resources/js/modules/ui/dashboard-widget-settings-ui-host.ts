import type {CpComponentRegistry} from '@/bootstrap/components';
import {createApp, defineComponent, h, ref, shallowRef, type App} from 'vue';
import UiRenderer from './UiRenderer.vue';
import type {UiPayload} from './types';

// TODO: Remove this legacy bridge once Dashboard widget settings are rendered by the Inertia/Vue Dashboard.

type UiErrors = Record<string, string | string[]>;
type UiRendererInstance = {
  currentValues(): UiPayload['values'];
};

export function defineDashboardWidgetSettingsUiHost(
  components: CpComponentRegistry
): void {
  if (customElements.get('craft-dashboard-widget-settings-ui')) {
    return;
  }

  customElements.define(
    'craft-dashboard-widget-settings-ui',
    class extends HTMLElement {
      readonly #payload = shallowRef<UiPayload | null>(null);
      readonly #errors = shallowRef<UiPayload['errors']>([]);
      readonly #renderer = ref<UiRendererInstance | null>(null);
      #app: App | null = null;
      #widgetType: string | null = null;

      set payload(payload: UiPayload | null) {
        this.#payload.value = payload;
        this.#errors.value = payload?.errors ?? [];
      }

      get payload(): UiPayload | null {
        return this.#payload.value;
      }

      set widgetType(widgetType: string | null) {
        this.#widgetType = widgetType;
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

      currentValues(): UiPayload['values'] {
        return this.#renderer.value?.currentValues() ?? {};
      }

      async #refresh(
        values: UiPayload['values'],
        scope: string[] = this.#payload.value?.scope ?? []
      ): Promise<UiPayload> {
        if (!this.#widgetType) {
          throw new Error('A widget type is required to refresh its settings.');
        }

        const {data} = await Craft.sendActionRequest(
          'POST',
          'dashboard/refresh-widget-settings',
          {
            data: {
              type: this.#widgetType,
              settings: values,
              namespace: scope.join('.'),
            },
          }
        );

        if (!data.form) {
          throw new Error('The widget did not return a UI payload.');
        }

        return data.form;
      }
    }
  );
}
