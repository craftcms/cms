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
import UiRenderer from './UiRenderer.vue';
import UpdateFieldLayoutController from '@/actions/CraftCms/Cms/Http/Controllers/Elements/UpdateFieldLayoutController';
import {isolateFieldUi, rebaseFieldUi, preserveFieldUis} from './html-field-ui';
import {prepareHtmlNestedOwner} from '@/modules/elements/html-nested-owner';
import {
  NestedOwnerEditorKey,
  nestedOwnerContext,
  requestNestedOwnerEditor,
  type NestedOwnerEditor,
} from '@/modules/elements/nested-owner';
import {canonical, inputName, isRecord, setValue, valueAt} from './runtime';
import type {UiPayload, UiValue, UiValues} from './types';

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

export interface EntryFieldLayoutUiHost extends HTMLElement {
  payload: UiPayload | null;
  fieldValue: UiValue;
  requestMetadata: () => UiValues;
}

const htmlFieldUis = new WeakMap<HTMLFormElement, Map<string, UiPayload>>();

class SupersededUiRefresh extends Error {}

function scopeContains(scope: string[], candidate: string[]): boolean {
  return scope.every((segment, index) => candidate[index] === segment);
}

function parseElementEditor(value: UiValue): ElementEditor | null {
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

export function defineEntryFieldLayoutUiHost(
  components: CpComponentRegistry
): void {
  if (customElements.get('craft-entry-field-layout-ui')) {
    return;
  }

  customElements.define(
    'craft-entry-field-layout-ui',
    class extends HTMLElement {
      readonly #payload = shallowRef<UiPayload | null>(null);
      #renderedPayload: UiPayload | null = null;
      readonly #renderer = shallowRef<{
        currentValues(): UiValues;
        resetValues(payload: UiPayload): void;
      } | null>(null);
      #app: App | null = null;
      #refreshEpoch = 0;
      #refreshSequence = 0;
      readonly #refreshes = new Map<
        string,
        {scope: string[]; sequence: number}
      >();
      #fieldPath: string[] | null = null;
      requestMetadata = (): UiValues => JSON.parse(this.dataset.owner ?? '{}');

      set payload(payload: UiPayload | null) {
        this.#invalidateRefreshes();
        if (
          payload &&
          this.#fieldPath &&
          this.dataset.fieldPath &&
          valueAt(payload.values, JSON.parse(this.dataset.fieldPath)) !==
            undefined
        ) {
          payload = rebaseFieldUi(
            payload,
            JSON.parse(this.dataset.fieldPath),
            this.#fieldPath
          );
        }
        if (payload && this.payload) preserveFieldUis(payload, this.payload);
        this.#payload.value = payload;
        this.#renderedPayload = payload;
      }

      set fieldValue(value: UiValue) {
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
        const payload: UiPayload = JSON.parse(
          JSON.stringify(this.#payload.value)
        );
        setValue(payload.values, this.#fieldPath, value);
        this.#renderer.value?.resetValues(payload);
        this.#payload.value = payload;
        this.#renderedPayload = payload;
        this.#rememberFieldUi();
      }

      get payload(): UiPayload | null {
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
              '[data-ui-field-name]'
            );
            this.#fieldPath = anchor
              ? anchor.name.replaceAll(']', '').split('[')
              : originalPath;
            this.#payload.value = rebaseFieldUi(
              this.#payload.value,
              originalPath,
              this.#fieldPath
            );
          }
          if (this.#fieldPath && this.#payload.value) {
            const form = this.closest('form');
            const previous =
              form &&
              htmlFieldUis.get(form)?.get(JSON.stringify(this.#fieldPath));
            if (previous) preserveFieldUis(this.#payload.value, previous);
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
                    if (!(error instanceof SupersededUiRefresh)) {
                      throw error;
                    }
                  }
                },
              });
              return () =>
                this.#payload.value
                  ? [
                      h(UiRenderer, {
                        ref: this.#renderer,
                        payload: this.#payload.value,
                        refresh: this.#refresh.bind(this),
                        onChange: () => {
                          this.#rememberFieldUi();
                          if (this.#fieldPath) {
                            void nextTick(() =>
                              this.dispatchEvent(
                                new Event('input', {bubbles: true})
                              )
                            );
                          }
                        },
                        'onUpdate:payload': (payload: UiPayload) => {
                          this.#renderedPayload = payload;
                          this.#rememberFieldUi();
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

      #rememberFieldUi(): void {
        const form = this.closest('form');
        const payload = this.payload;
        if (!form || !this.#fieldPath || !payload) return;

        const fields = htmlFieldUis.get(form) ?? new Map<string, UiPayload>();
        fields.set(JSON.stringify(this.#fieldPath), payload);
        htmlFieldUis.set(form, fields);
      }

      async #refresh(
        _values: UiPayload['values'],
        scope: string[] = this.#payload.value?.scope ?? []
      ): Promise<UiPayload> {
        const form = this.closest('form');
        const editor = form
          ? parseElementEditor($(form).data('elementEditor'))
          : null;

        if (!form || (!editor && !this.dataset.owner)) {
          throw new Error('Entry UI refresh requires an Element Editor.');
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
          ':scope > [data-ui-tab]:not(.hidden)'
        )?.dataset.id;
        if (selectedTab) {
          data.set(inputName([...rootScope, 'selectedTab']), selectedTab);
        }

        const headers = {
          'X-Craft-Namespace': rootScope.length
            ? inputName(rootScope)
            : undefined,
          'X-Craft-Ui-Root-Scope': JSON.stringify(rootScope),
          'X-Craft-Native-Field-Path': this.#fieldPath
            ? JSON.stringify(this.#fieldPath)
            : undefined,
          'X-Craft-Ui-Scope': JSON.stringify(
            this.#fieldPath ? rootScope : scope
          ),
        };

        const {data: response} = await actionClient.post(
          UpdateFieldLayoutController.url(),
          data.toString(),
          {headers}
        );

        if (!response.ui) {
          throw new Error('The Entry FieldLayout did not return a UI payload.');
        }

        this.#assertCurrentRefresh(scope, epoch, sequence);

        await appendHeadHtml(response.headHtml);

        this.#assertCurrentRefresh(scope, epoch, sequence);

        await appendBodyHtml(response.bodyHtml);

        this.#assertCurrentRefresh(scope, epoch, sequence);

        editor?.handleDismissibleTips?.();

        return this.#fieldPath
          ? isolateFieldUi(response.ui, this.#fieldPath, scope)
          : response.ui;
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
          throw new SupersededUiRefresh();
        }
      }
    }
  );
}
