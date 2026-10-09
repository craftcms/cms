import {mountUiHost} from './mountUiHost';
import type {CpComponentRegistry} from '@/bootstrap/components';
import {renderUi} from '@/actions/CraftCms/Cms/Http/Controllers/FieldsController';
import {actionClient} from '@craftcms/ui';
import {h} from 'vue';
import UiRenderer from './UiRenderer.vue';
import {scopeUiPayload} from './uiScope';
import type {UiPayload} from './types';

export function defineFieldSettingsUiHost(
  components: CpComponentRegistry
): void {
  if (customElements.get('craft-field-settings-ui')) return;

  customElements.define(
    'craft-field-settings-ui',
    class extends HTMLElement {
      #mount: ReturnType<typeof mountUiHost> | null = null;
      ready: Promise<void> = Promise.resolve();

      connectedCallback(): void {
        if (this.#mount) return;
        const scope = (this.getAttribute('name') ?? '__fieldSettings')
          .replaceAll(']', '')
          .split('[')
          .slice(0, -1);
        const payload = scopeUiPayload(
          JSON.parse(this.dataset.payload!),
          scope
        );
        this.#mount = mountUiHost(this, components, () =>
          h(UiRenderer, {
            payload,
            refresh: payload.refreshable
              ? async (settings: UiPayload['values']) => {
                  const {data} = await actionClient.post<{ui: UiPayload}>(
                    renderUi.url(),
                    {
                      values: {
                        type: this.dataset.fieldType,
                        fieldId: this.dataset.fieldId
                          ? Number(this.dataset.fieldId)
                          : null,
                        settings,
                      },
                      scope: [],
                      settingsOnly: true,
                    }
                  );
                  if (!data.ui)
                    throw new Error(
                      'The field did not return its settings UI.'
                    );
                  return scopeUiPayload(data.ui, scope);
                }
              : undefined,
          })
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
