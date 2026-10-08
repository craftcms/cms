import type {CpComponentRegistry} from '@/bootstrap/components';
import UpdateFieldLayoutController from '@/actions/CraftCms/Cms/Http/Controllers/Elements/UpdateFieldLayoutController';
import {actionClient, appendBodyHtml, appendHeadHtml, t} from '@craftcms/ui';
import {createApp, defineComponent, h, shallowRef, type App} from 'vue';
import {prepareHtmlNestedOwner} from '@/modules/elements/html-nested-owner';
import {
  requestNestedOwnerEditor,
  type NestedOwnerContext,
  type NestedOwnerEditor,
} from '@/modules/elements/nested-owner';
import {inputName, pathsMatch, visitControls} from '../runtime';
import type {UiControlPayload, UiPayload} from '../types';
import NestedElements from './NestedElements.vue';
import type {NestedElementsProps} from './nested-elements';

/** Mounts native field-layout controls without replacing their surrounding HTML form. */
export function defineNestedElementsControlHost(
  components: CpComponentRegistry
): void {
  if (customElements.get('craft-nested-elements-control')) {
    return;
  }

  customElements.define(
    'craft-nested-elements-control',
    class extends HTMLElement {
      #app: App | null = null;
      #epoch = 0;
      #preparedOwner: NestedOwnerContext | null = null;
      #scope: string[] = [];
      readonly #control =
        shallowRef<UiControlPayload<NestedElementsProps> | null>(null);

      set control(control: UiControlPayload<NestedElementsProps>) {
        this.#epoch++;
        this.#control.value = {
          ...control,
          path: this.#control.value?.path ?? control.path,
          deltaGroup: this.#control.value?.deltaGroup ?? control.deltaGroup,
        };
        this.dataset.control = JSON.stringify(control);
      }

      connectedCallback(): void {
        if (this.#app) {
          return;
        }

        let control: UiControlPayload<NestedElementsProps> = JSON.parse(
          this.dataset.control!
        );
        this.#scope = JSON.parse(this.dataset.scope ?? '[]');
        const input = this.querySelector<HTMLInputElement>(
          '[data-nested-modified]'
        );
        if (input) {
          const path = input.name.replaceAll(']', '').split('[');
          if (path.length >= control.path.length) {
            const prefix = path.slice(0, path.length - control.path.length);
            control = {...control, path};
            this.#scope = [...prefix, ...this.#scope];
          }
        }
        this.#control.value = control;

        let nativeOwner: NestedOwnerEditor | null = null;
        const owner: NestedOwnerEditor = {
          prepare: async () => {
            const form = this.closest('form');
            const manager = this.#control.value?.props.manager;
            if (!manager || this.#control.value?.mode !== 'editable') {
              return null;
            }

            const context: NestedOwnerContext = {
              ownerId: manager.ownerId,
              ownerIsDerivative: manager.ownerIsDerivative === true,
              ownerIsInDerivativeTree: manager.ownerIsInDerivativeTree === true,
              ownerIsUnpublishedDraft: manager.ownerIsUnpublishedDraft === true,
              requiresDerivative: manager.ownerHasDrafts !== false,
            };
            nativeOwner = requestNestedOwnerEditor(this);
            this.#preparedOwner = nativeOwner
              ? await nativeOwner.prepare(this.#control.value.path, context)
              : form
                ? await prepareHtmlNestedOwner(
                    form,
                    context,
                    inputName(this.#control.value.path)
                  )
                : null;
            return this.isConnected ? this.#preparedOwner : null;
          },
          refresh: () => nativeOwner?.refresh?.() ?? this.#refresh(),
        };
        this.#app = createApp(
          defineComponent({
            setup: () => () =>
              this.#control.value
                ? h(NestedElements, {
                    path: this.#control.value.path,
                    nested: this.#control.value.props,
                    editable: this.#control.value.mode === 'editable',
                    owner,
                    onModified: () => {
                      if (input && this.#control.value?.mode === 'editable') {
                        input.disabled = false;
                        input.dispatchEvent(
                          new Event('change', {bubbles: true})
                        );
                      }
                    },
                  })
                : null,
          })
        );
        this.#app.config.compilerOptions.isCustomElement = (tag) =>
          tag.includes('-');
        components.install(this.#app);
        this.#app.mount(
          this.querySelector<HTMLElement>('[data-nested-mount]')!
        );
      }

      async #refresh(): Promise<void> {
        const form = this.closest('form');
        const control = this.#control.value;
        const manager = control?.props.manager;
        if (!form || !control || !manager) {
          return;
        }

        const epoch = ++this.#epoch;
        const data = new URLSearchParams($(form).serialize());
        for (const name of ['draftId', 'revisionId', 'ownerId', 'fieldId']) {
          data.delete(inputName([...this.#scope, name]));
        }
        for (const [name, value] of Object.entries({
          elementType: manager.ownerElementType,
          elementId: this.#preparedOwner?.ownerId ?? manager.ownerId,
          siteId: manager.ownerSiteId,
        })) {
          data.set(inputName([...this.#scope, name]), String(value));
        }

        const {data: response} = await actionClient.post<{
          ui: UiPayload;
          headHtml?: string;
          bodyHtml?: string;
        }>(UpdateFieldLayoutController.url(), data.toString(), {
          headers: {
            'X-Craft-Namespace': this.#scope.length
              ? inputName(this.#scope)
              : undefined,
            'X-Craft-Ui-Root-Scope': JSON.stringify(this.#scope),
            'X-Craft-Native-Field-Path': JSON.stringify(control.path),
          },
        });
        if (epoch !== this.#epoch) {
          return;
        }

        let refreshed: UiControlPayload<NestedElementsProps> | null = null;
        visitControls(response.ui.nodes, (candidate) => {
          if (
            candidate.component === 'craft:nested-elements' &&
            pathsMatch(candidate.path, control.path)
          ) {
            refreshed = candidate as UiControlPayload<NestedElementsProps>;
          }
        });
        if (!refreshed) {
          throw new Error(
            t('The nested element field is no longer available.')
          );
        }

        await appendHeadHtml(response.headHtml ?? '');
        if (epoch !== this.#epoch) {
          return;
        }
        await appendBodyHtml(response.bodyHtml ?? '');
        if (epoch === this.#epoch) {
          this.#control.value = refreshed;
        }
      }

      disconnectedCallback(): void {
        this.#epoch++;
        this.#preparedOwner = null;
        if (this.#app) {
          components.uninstall(this.#app);
          this.#app.unmount();
          this.#app = null;
        }
      }
    }
  );
}
