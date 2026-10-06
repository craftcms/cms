import type {CpComponentRegistry} from '@/bootstrap/components';
import {actionClient, appendBodyHtml, appendHeadHtml} from '@craftcms/ui';
import {
  createApp,
  defineComponent,
  h,
  nextTick,
  provide,
  shallowRef,
  type App,
} from 'vue';
import FormRenderer from './FormRenderer.vue';
import UpdateFieldLayoutController from '@/actions/CraftCms/Cms/Http/Controllers/Elements/UpdateFieldLayoutController';
import {
  isolateFieldForm,
  rebaseFieldForm,
  preserveFieldForms,
} from './html-field-form';
import {prepareHtmlNestedOwner} from '@/modules/elements/html-nested-owner';
import {
  NestedOwnerEditorKey,
  nestedOwnerContext,
  requestNestedOwnerEditor,
  type NestedOwnerEditor,
} from '@/modules/elements/nested-owner';
import {canonical, inputName, isRecord, setValue, valueAt} from './runtime';
import type {FormPayload, FormValue, FormValues} from './types';

type ElementEditor = {
  settings: {
    elementType: string;
    elementId: number | null;
    canonicalId: number | null;
    draftId: number | null;
    revisionId: number | null;
    fieldId: number | null;
    ownerId: number | null;
    siteId: number | null;
    isProvisionalDraft: boolean;
  };
  handleDismissibleTips?: () => void;
};

export interface EntryFieldLayoutFormHost extends HTMLElement {
  payload: FormPayload | null;
  fieldValue: FormValue;
  requestMetadata: () => FormValues;
}

const htmlFieldForms = new WeakMap<HTMLFormElement, Map<string, FormPayload>>();

class SupersededFormRefresh extends Error {}

function scopeContains(scope: string[], candidate: string[]): boolean {
  return scope.every((segment, index) => candidate[index] === segment);
}

function parseElementEditor(value: FormValue): ElementEditor | null {
  if (!isRecord(value) || !isRecord(value.settings)) {
    return null;
  }

  const settings = value.settings;
  const nullableNumberKeys = [
    'elementId',
    'canonicalId',
    'draftId',
    'revisionId',
    'fieldId',
    'ownerId',
    'siteId',
  ];
  const valid =
    Object(settings.elementType).constructor === String &&
    Boolean(settings.isProvisionalDraft) === settings.isProvisionalDraft &&
    nullableNumberKeys.every(
      (key) => settings[key] === null || Number(settings[key]) === settings[key]
    );

  if (!valid) {
    return null;
  }

  const nullableNumber = (key: string): number | null =>
    settings[key] === null ? null : Number(settings[key]);

  return {
    settings: {
      elementType: String(settings.elementType),
      elementId: nullableNumber('elementId'),
      canonicalId: nullableNumber('canonicalId'),
      draftId: nullableNumber('draftId'),
      revisionId: nullableNumber('revisionId'),
      fieldId: nullableNumber('fieldId'),
      ownerId: nullableNumber('ownerId'),
      siteId: nullableNumber('siteId'),
      isProvisionalDraft: Boolean(settings.isProvisionalDraft),
    },
  };
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
      #renderedPayload: FormPayload | null = null;
      readonly #renderer = shallowRef<{
        currentValues(): FormValues;
        resetValues(payload: FormPayload): void;
      } | null>(null);
      #app: App | null = null;
      #refreshEpoch = 0;
      #refreshSequence = 0;
      readonly #refreshes = new Map<
        string,
        {scope: string[]; sequence: number}
      >();
      #fieldPath: string[] | null = null;
      requestMetadata = (): FormValues =>
        JSON.parse(this.dataset.owner ?? '{}');

      set payload(payload: FormPayload | null) {
        this.#invalidateRefreshes();
        if (
          payload &&
          this.#fieldPath &&
          this.dataset.fieldPath &&
          valueAt(payload.values, JSON.parse(this.dataset.fieldPath)) !==
            undefined
        ) {
          payload = rebaseFieldForm(
            payload,
            JSON.parse(this.dataset.fieldPath),
            this.#fieldPath
          );
        }
        if (payload && this.payload) preserveFieldForms(payload, this.payload);
        this.#payload.value = payload;
        this.#renderedPayload = payload;
      }

      set fieldValue(value: FormValue) {
        if (!this.#fieldPath || !this.#payload.value) return;
        const current = valueAt(this.payload?.values ?? {}, this.#fieldPath);
        if (
          isRecord(current) &&
          canonical({
            entries: current.entries,
            sortOrder: current.sortOrder,
          }) === canonical(value)
        )
          return;
        this.#invalidateRefreshes();
        const payload: FormPayload = JSON.parse(
          JSON.stringify(this.#payload.value)
        );
        setValue(payload.values, this.#fieldPath, value);
        this.#renderer.value?.resetValues(payload);
        this.#payload.value = payload;
        this.#renderedPayload = payload;
        this.#rememberFieldForm();
      }

      get payload(): FormPayload | null {
        return this.#renderedPayload
          ? {
              ...this.#renderedPayload,
              values:
                this.#renderer.value?.currentValues() ??
                this.#renderedPayload.values,
            }
          : null;
      }

      connectedCallback(): void {
        if (this.#app) {
          return;
        }

        if (this.#renderedPayload) {
          this.#payload.value = this.#renderedPayload;
        } else {
          this.#payload.value = JSON.parse(this.dataset.payload ?? 'null');
          if (this.dataset.fieldPath && this.#payload.value) {
            const originalPath: string[] = JSON.parse(this.dataset.fieldPath);
            const anchor = this.querySelector<HTMLInputElement>(
              '[data-form-field-name]'
            );
            this.#fieldPath = anchor
              ? anchor.name.replaceAll(']', '').split('[')
              : originalPath;
            this.#payload.value = rebaseFieldForm(
              this.#payload.value,
              originalPath,
              this.#fieldPath
            );
          }
          if (this.#fieldPath && this.#payload.value) {
            const form = this.closest('form');
            const previous =
              form &&
              htmlFieldForms.get(form)?.get(JSON.stringify(this.#fieldPath));
            if (previous) preserveFieldForms(this.#payload.value, previous);
          }
          this.#renderedPayload = this.#payload.value;
        }
        this.#app = createApp(
          defineComponent({
            setup: () => {
              let nativeOwner: NestedOwnerEditor | null = null;
              provide(NestedOwnerEditorKey, {
                prepare: async (path) => {
                  const form = this.closest('form');
                  const context = nestedOwnerContext(
                    this.#renderedPayload,
                    path
                  );
                  if (!context) return null;
                  nativeOwner = requestNestedOwnerEditor(this);
                  if (nativeOwner) {
                    const prepared = await nativeOwner.prepare(path, context);
                    return this.isConnected ? prepared : null;
                  }
                  if (!form) {
                    return null;
                  }

                  return prepareHtmlNestedOwner(form, context, inputName(path));
                },
                resolveElementId: (id) => {
                  if (nativeOwner)
                    return nativeOwner.resolveElementId?.(id) ?? id;
                  const form = this.closest('form');
                  const editor = form
                    ? ($(form).data('elementEditor') as
                        | {getDraftElementId?: (id: number) => number}
                        | undefined)
                    : undefined;
                  return editor?.getDraftElementId?.(id) ?? id;
                },
                refresh: async () => {
                  const parent = nativeOwner ?? requestNestedOwnerEditor(this);
                  if (parent?.refresh) return parent.refresh();
                  if (!this.#payload.value) {
                    return;
                  }

                  try {
                    this.#payload.value = await this.#refresh(
                      this.#payload.value.values
                    );
                  } catch (error) {
                    if (!(error instanceof SupersededFormRefresh)) {
                      throw error;
                    }
                  }
                },
              });
              return () =>
                this.#payload.value
                  ? [
                      h(FormRenderer, {
                        ref: this.#renderer,
                        payload: this.#payload.value,
                        refresh: this.#refresh.bind(this),
                        onChange: () => {
                          this.#rememberFieldForm();
                          if (this.#fieldPath) {
                            void nextTick(() =>
                              this.dispatchEvent(
                                new Event('input', {bubbles: true})
                              )
                            );
                          }
                        },
                        'onUpdate:payload': (payload: FormPayload) => {
                          this.#renderedPayload = payload;
                          this.#rememberFieldForm();
                        },
                      }),
                    ]
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
        this.#invalidateRefreshes();
        this.#renderedPayload = this.payload;

        if (this.#app) {
          components.uninstall(this.#app);
          this.#app.unmount();
        }
        this.#app = null;
      }

      #rememberFieldForm(): void {
        const form = this.closest('form');
        const payload = this.payload;
        if (!form || !this.#fieldPath || !payload) return;

        const fields =
          htmlFieldForms.get(form) ?? new Map<string, FormPayload>();
        fields.set(JSON.stringify(this.#fieldPath), payload);
        htmlFieldForms.set(form, fields);
      }

      async #refresh(
        _values: FormPayload['values'],
        scope: string[] = this.#payload.value?.scope ?? []
      ): Promise<FormPayload> {
        const form = this.closest('form');
        const editor = form
          ? parseElementEditor($(form).data('elementEditor'))
          : null;

        if (!form || (!editor && !this.dataset.owner)) {
          throw new Error('Entry Form refresh requires an Element Editor.');
        }

        const rootScope = this.#payload.value?.scope ?? [];
        const scopeKey = JSON.stringify(scope);
        if (scopeKey === JSON.stringify(rootScope)) {
          this.#invalidateRefreshes();
        }
        const epoch = this.#refreshEpoch;
        const sequence = ++this.#refreshSequence;
        this.#refreshes.set(scopeKey, {scope, sequence});
        const data = new URLSearchParams($(form).serialize());
        const metadata = this.#fieldPath
          ? this.requestMetadata()
          : {
              elementType: editor!.settings.elementType,
              elementId: editor!.settings.elementId,
              draftId: editor!.settings.draftId,
              revisionId: editor!.settings.revisionId,
              fieldId: editor!.settings.fieldId,
              ownerId: editor!.settings.ownerId,
              siteId: editor!.settings.siteId,
              provisional: editor!.settings.isProvisionalDraft ? 1 : null,
              ...this.requestMetadata(),
            };
        if (this.#fieldPath) {
          const ownerEditor = $(form).data('elementEditor') as
            | {
                getDraftElementId?: (id: number) => number;
              }
            | undefined;
          if (typeof metadata.elementId === 'number') {
            metadata.elementId =
              ownerEditor?.getDraftElementId?.(metadata.elementId) ??
              metadata.elementId;
          }
          for (const name of ['draftId', 'revisionId', 'ownerId', 'fieldId']) {
            data.delete(inputName([...rootScope, name]));
          }
        }
        for (const [name, value] of Object.entries(metadata)) {
          if (value !== null && value !== undefined) {
            data.set(inputName([...rootScope, name]), String(value));
          }
        }
        const selectedTab = this.querySelector<HTMLElement>(
          ':scope > [data-form-tab]:not(.hidden)'
        )?.dataset.id;
        if (selectedTab) {
          data.set(inputName([...rootScope, 'selectedTab']), selectedTab);
        }

        const headers = {
          'X-Craft-Namespace': rootScope.length
            ? inputName(rootScope)
            : undefined,
          'X-Craft-Form-Root-Scope': JSON.stringify(rootScope),
          'X-Craft-Native-Field-Path': this.#fieldPath
            ? JSON.stringify(this.#fieldPath)
            : undefined,
          'X-Craft-Form-Scope': JSON.stringify(
            this.#fieldPath ? rootScope : scope
          ),
        };

        const {data: response} = await actionClient.post(
          UpdateFieldLayoutController.url(),
          data.toString(),
          {headers}
        );

        if (!response.form) {
          throw new Error(
            'The Entry FieldLayout did not return a Form payload.'
          );
        }

        this.#assertCurrentRefresh(scope, epoch, sequence);

        await appendHeadHtml(response.headHtml);

        this.#assertCurrentRefresh(scope, epoch, sequence);

        await appendBodyHtml(response.bodyHtml);

        this.#assertCurrentRefresh(scope, epoch, sequence);

        editor?.handleDismissibleTips?.();

        return this.#fieldPath
          ? isolateFieldForm(response.form, this.#fieldPath, scope)
          : response.form;
      }

      #invalidateRefreshes(): void {
        this.#refreshEpoch++;
        this.#refreshes.clear();
      }

      #assertCurrentRefresh(
        scope: string[],
        epoch: number,
        sequence: number
      ): void {
        if (
          epoch !== this.#refreshEpoch ||
          [...this.#refreshes.values()].some(
            (refresh) =>
              refresh.sequence > sequence &&
              (scopeContains(refresh.scope, scope) ||
                scopeContains(scope, refresh.scope))
          )
        ) {
          throw new SupersededFormRefresh();
        }
      }
    }
  );
}
