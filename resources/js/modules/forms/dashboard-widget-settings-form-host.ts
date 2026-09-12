import type {CpComponentRegistry} from '@/bootstrap/components';
import {createApp, defineComponent, h, ref, shallowRef, type App} from 'vue';
import FormRenderer from './FormRenderer.vue';
import type {FormPayload} from './types';

// TODO: Remove this legacy bridge once Dashboard widget settings are rendered by the Inertia/Vue Dashboard.

type FormErrors = Record<string, string | string[]>;
type FormRendererInstance = {
    currentValues(): FormPayload['values'];
};

export function defineDashboardWidgetSettingsFormHost(
    components: CpComponentRegistry
): void {
    if (customElements.get('craft-dashboard-widget-settings-form')) {
        return;
    }

    customElements.define(
        'craft-dashboard-widget-settings-form',
        class extends HTMLElement {
            readonly #payload = shallowRef<FormPayload | null>(null);
            readonly #errors = shallowRef<FormPayload['errors']>([]);
            readonly #renderer = ref<FormRendererInstance | null>(null);
            #app: App | null = null;
            #widgetType: string | null = null;

            set payload(payload: FormPayload | null) {
                this.#payload.value = payload;
                this.#errors.value = payload?.errors ?? [];
            }

            get payload(): FormPayload | null {
                return this.#payload.value;
            }

            set widgetType(widgetType: string | null) {
                this.#widgetType = widgetType;
            }

            set errors(errors: FormErrors) {
                const scope = this.#payload.value?.scope ?? [];
                this.#errors.value = Object.entries(errors).map(
                    ([path, messages]) => ({
                        path: [...scope, ...path.split('.')],
                        messages: Array.isArray(messages)
                            ? messages
                            : [messages],
                    })
                );
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
                                          refresh: this.#payload.value
                                              .refreshable
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

            currentValues(): FormPayload['values'] {
                return this.#renderer.value?.currentValues() ?? {};
            }

            async #refresh(
                values: FormPayload['values'],
                scope: string[] = this.#payload.value?.scope ?? []
            ): Promise<FormPayload> {
                if (!this.#widgetType) {
                    throw new Error(
                        'A widget type is required to refresh its settings.'
                    );
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
                    throw new Error(
                        'The widget did not return a Form payload.'
                    );
                }

                return data.form;
            }
        }
    );
}
