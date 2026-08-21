import type {CpComponentRegistry} from '@/bootstrap/components';
import {actionClient} from '@craftcms/ui';
import {createApp, defineComponent, h, shallowRef, type App} from 'vue';
import FormRenderer from './FormRenderer.vue';
import {inputName} from './runtime';
import type {FormPayload} from './types';

type ElementEditor = {
    settings: Record<string, any>;
    handleDismissibleTips?: () => void;
};

type CraftRuntime = typeof Craft & {
    appendHeadHtml(html: string): Promise<void>;
    appendBodyHtml(html: string): Promise<void>;
};

export interface EntryFieldLayoutFormHost extends HTMLElement {
    payload: FormPayload | null;
    requestMetadata: () => Record<string, unknown>;
}

export function defineEntryFieldLayoutFormHost(
    components: CpComponentRegistry
): void {
    if (customElements.get('craft-entry-field-layout-form')) {
        return;
    }

    customElements.define(
        'craft-entry-field-layout-form',
        class extends HTMLElement {
            readonly #payload = shallowRef<FormPayload | null>(null);
            #app: App | null = null;
            requestMetadata = (): Record<string, unknown> => ({});

            set payload(payload: FormPayload | null) {
                this.#payload.value = payload;
            }

            get payload(): FormPayload | null {
                return this.#payload.value;
            }

            connectedCallback(): void {
                if (this.#app) {
                    return;
                }

                this.#payload.value = JSON.parse(
                    this.dataset.payload ?? 'null'
                );
                this.#app = createApp(
                    defineComponent({
                        setup: () => () =>
                            this.#payload.value
                                ? [
                                      h(FormRenderer, {
                                          payload: this.#payload.value,
                                          refresh: this.#refresh.bind(this),
                                      }),
                                  ]
                                : null,
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

            async #refresh(
                _values: FormPayload['values'],
                scope: string[] = this.#payload.value?.scope ?? []
            ): Promise<FormPayload> {
                const form = this.closest('form');
                const editor = form
                    ? ($(form).data('elementEditor') as ElementEditor)
                    : null;

                if (!form || !editor) {
                    throw new Error(
                        'Entry Form refresh requires an Element Editor.'
                    );
                }

                const rootScope = this.#payload.value?.scope ?? [];
                const data = new URLSearchParams($(form).serialize());
                const metadata = {
                    elementType: editor.settings.elementType,
                    elementId: editor.settings.elementId,
                    canonicalId: editor.settings.canonicalId,
                    draftId: editor.settings.draftId,
                    revisionId: editor.settings.revisionId,
                    fieldId: editor.settings.fieldId,
                    ownerId: editor.settings.ownerId,
                    siteId: editor.settings.siteId,
                    provisional: editor.settings.isProvisionalDraft ? 1 : null,
                    ...this.requestMetadata(),
                };
                for (const [name, value] of Object.entries(metadata)) {
                    if (value !== null && value !== undefined) {
                        data.set(
                            inputName([...rootScope, name]),
                            String(value)
                        );
                    }
                }
                const selectedTab = this.querySelector<HTMLElement>(
                    ':scope > [data-form-tab]:not(.hidden)'
                )?.dataset.id;
                if (selectedTab) {
                    data.set(
                        inputName([...rootScope, 'selectedTab']),
                        selectedTab
                    );
                }

                const craft = Craft as CraftRuntime;
                const {data: response} = await actionClient.post(
                    'elements/update-field-layout',
                    data.toString(),
                    {
                        headers: {
                            ...(rootScope.length
                                ? {'X-Craft-Namespace': inputName(rootScope)}
                                : {}),
                            'X-Craft-Form-Root-Scope':
                                JSON.stringify(rootScope),
                            'X-Craft-Form-Scope': JSON.stringify(scope),
                        },
                    }
                );

                if (!response.form) {
                    throw new Error(
                        'The Entry FieldLayout did not return a Form payload.'
                    );
                }

                await craft.appendHeadHtml(response.headHtml);
                await craft.appendBodyHtml(response.bodyHtml);
                editor.handleDismissibleTips?.();

                return response.form;
            }
        }
    );
}
