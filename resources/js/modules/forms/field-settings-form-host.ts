import {mountFormHost} from './mountFormHost';
import type {CpComponentRegistry} from '@/bootstrap/components';
import {renderForm} from '@/actions/CraftCms/Cms/Http/Controllers/FieldsController';
import {actionClient} from '@craftcms/ui';
import {h} from 'vue';
import FormRenderer from './FormRenderer.vue';
import {scopeFormPayload} from './formScope';
import type {FormPayload} from './types';

export function defineFieldSettingsFormHost(
  components: CpComponentRegistry
): void {
  if (customElements.get('craft-field-settings-form')) return;

  customElements.define(
    'craft-field-settings-form',
    class extends HTMLElement {
      #mount: ReturnType<typeof mountFormHost> | null = null;
      ready: Promise<void> = Promise.resolve();

      connectedCallback(): void {
        if (this.#mount) return;
        const scope = (this.getAttribute('name') ?? '__fieldSettings')
          .replaceAll(']', '')
          .split('[')
          .slice(0, -1);
        const payload = scopeFormPayload(
          JSON.parse(this.dataset.payload!),
          scope
        );
        this.#mount = mountFormHost(this, components, () =>
          h(FormRenderer, {
            payload,
            refresh: payload.refreshable
              ? async (settings: FormPayload['values']) => {
                  const {data} = await actionClient.post<{form: FormPayload}>(
                    renderForm.url(),
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
                  if (!data.form)
                    throw new Error(
                      'The field did not return its settings Form.'
                    );
                  return scopeFormPayload(data.form, scope);
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
