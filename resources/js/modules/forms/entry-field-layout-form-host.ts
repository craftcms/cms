import type {CpComponentRegistry} from '@/bootstrap/components';
import {actionClient, appendBodyHtml, appendHeadHtml} from '@craftcms/ui';
import {
  createApp,
  defineComponent,
  h,
  provide,
  shallowRef,
  type App,
} from 'vue';
import FormRenderer from './FormRenderer.vue';
import {
  NestedOwnerEditorKey,
  nestedOwnerContext,
} from '@/modules/elements/nested-owner';
import {inputName, isRecord} from './runtime';
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
  requestMetadata: () => FormValues;
}

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
      #app: App | null = null;
      #refreshEpoch = 0;
      #refreshSequence = 0;
      readonly #refreshes = new Map<
        string,
        {scope: string[]; sequence: number}
      >();
      requestMetadata = (): FormValues => ({});

      set payload(payload: FormPayload | null) {
        this.#invalidateRefreshes();
        this.#payload.value = payload;
        this.#renderedPayload = payload;
      }

      get payload(): FormPayload | null {
        return this.#payload.value;
      }

      connectedCallback(): void {
        if (this.#app) {
          return;
        }

        this.#payload.value = JSON.parse(this.dataset.payload ?? 'null');
        this.#renderedPayload = this.#payload.value;
        this.#app = createApp(
          defineComponent({
            setup: () => {
              provide(NestedOwnerEditorKey, {
                prepare: async (path) => {
                  const form = this.closest('form');
                  const context = nestedOwnerContext(
                    this.#renderedPayload,
                    path
                  );
                  if (!form || !context) {
                    return null;
                  }

                  const editor = $(form).data('elementEditor') as
                    | {
                        settings?: {
                          isStatic?: boolean;
                          canCreateDrafts?: boolean;
                          draftId?: number | null;
                          canonicalId?: number | null;
                          isProvisionalDraft?: boolean;
                        };
                        saveDraft?: () => Promise<void>;
                        getDraftElementId?: (id: number) => number;
                      }
                    | undefined;
                  if (!editor || editor.settings?.isStatic) {
                    return null;
                  }

                  if (editor.settings?.canCreateDrafts) {
                    await editor.saveDraft?.();
                    if (!editor.settings.draftId) {
                      return null;
                    }
                  }

                  const ownerId =
                    editor.getDraftElementId?.(context.ownerId) ??
                    context.ownerId;
                  return {
                    ...context,
                    ownerId,
                    canonicalId: editor.settings?.canonicalId,
                    draftId: editor.settings?.draftId,
                    isProvisionalDraft: editor.settings?.isProvisionalDraft,
                    ownerIsDerivative:
                      ownerId !== context.ownerId || context.ownerIsDerivative,
                    ownerIsInDerivativeTree:
                      ownerId !== context.ownerId ||
                      context.ownerIsInDerivativeTree,
                    requiresDerivative: Boolean(
                      editor.settings?.canCreateDrafts
                    ),
                  };
                },
                refresh: async () => {
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
                        payload: this.#payload.value,
                        refresh: this.#refresh.bind(this),
                        'onUpdate:payload': (payload: FormPayload) => {
                          this.#renderedPayload = payload;
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
          ? parseElementEditor($(form).data('elementEditor'))
          : null;

        if (!form || !editor) {
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
        const metadata = {
          elementType: editor.settings.elementType,
          elementId: editor.settings.elementId,
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
          'X-Craft-Form-Scope': JSON.stringify(scope),
        };

        const {data: response} = await actionClient.post(
          'elements/update-field-layout',
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

        editor.handleDismissibleTips?.();

        return response.form;
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
