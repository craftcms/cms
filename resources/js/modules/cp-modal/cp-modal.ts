import {Base, isMobileBrowser, setFocusWithin} from '@craftcms/garnish';
import {inputName} from '@/modules/ui/runtime';
import type {UiValues} from '@/modules/ui/types';
import {isHttpError} from '@craftcms/ui/utilities/api/http';
import {
  sendLegacyRequest,
  type LegacyRequestOptions,
} from '@craftcms/ui/utilities/api/legacyRequest';
import {refreshComponentInstances} from '@/common/craft/component-instances';
import {initLegacyUi} from '@/common/craft/legacy-ui';
import {loadElementThumbs} from '@/modules/element-thumb-loader/globals';
import {
  addErrorsToField,
  clearErrorsFromField,
} from '@/common/craft/field-errors';
import {actionClient} from '@craftcms/ui/utilities/api/actionClient';
import {getCpUrl} from '@/common/craft/url';
import {commandShortcut} from '@/common/utils/keyShortcuts';
import {registerShortcut, type ShortcutRegistration} from '@/common/shortcuts';
import {findDeltaData} from '@/common/craft/delta';
import {t} from '@craftcms/ui/utilities/translate';
import {
  appendBodyHtml,
  appendElementHtml,
  appendHeadHtml,
  serializeFormInputs,
} from '@craftcms/ui/utilities/dom';
import {jq} from '@/common/utils/jquery';
import {setElementAttributes} from '@/common/craft/attributes';
import {showError, showSuccess} from '@/modules/messages/messages';

declare const Craft: any;

/** A `craft-dialog`. */
interface CpModalDialog extends HTMLElement {
  opened: boolean;
  noHeader: boolean;
  label: string;
}

interface CpModalSettings {
  /** The dialog's heading. Without one, the dialog has no header. */
  title: string | null;
  params: UiValues;
  containerElement: string;
  containerAttributes: Record<string, string>;
  requestOptions: LegacyRequestOptions;
  closeOnSubmit: boolean;
  showSubmitButton: boolean;
  onSubmit: (event?: CpModalSubmitEvent) => void;
  onCancel: () => void;
}

interface CpModalSubmitEvent {
  response: {data?: UiValues};
  data: UiValues;
}

interface CpModalRequestError extends Error {
  response?: {
    status?: number;
    data?: {message?: string; errors?: Record<string, string[]>};
  };
}

const DEFAULTS: CpModalSettings = {
  title: null,
  params: {},
  containerElement: 'form',
  containerAttributes: {
    action: '',
    method: 'post',
    novalidate: '',
    class: 'cpmodal',
  },
  requestOptions: {},
  closeOnSubmit: true,
  showSubmitButton: true,
  onSubmit: () => {},
  onCancel: () => {},
};

/**
 * CpModal — `Craft.CpModal`, on a `craft-dialog`. Loads a control-panel screen
 * (`action`) into a form in the dialog, then submits it with delta-encoded data
 * and renders validation errors inline. Booted from PHP deletion blockers
 * (`new Craft.CpModal('…', {…})`).
 *
 * Emits `beforeLoad`, `load`, `submit`, `close` and `cancel`.
 */
export class CpModal extends Base {
  action: string | null = null;
  namespace: string | null = null;
  succeeded = false;
  visible = false;
  dialog: CpModalDialog;

  #cpSettings: CpModalSettings;
  #abortController: AbortController | null = null;
  #ignoreFailedRequest = false;
  #fieldsWithErrors: ArrayLike<Element>[] = [];
  #shortcuts: ShortcutRegistration;

  #container: HTMLFormElement;
  #content: HTMLElement;
  #loadSpinner: HTMLElement & {hidden: boolean};
  #cancelBtn: HTMLElement;
  #saveBtn: (HTMLElement & {loading?: boolean}) | null = null;
  #deltaNames: string[] = [];
  #initialDeltaValues: UiValues = {};
  #initialSerializedValue: string | undefined;

  constructor(action?: string, settings?: Partial<CpModalSettings>) {
    super();

    this.#cpSettings = Object.assign({}, DEFAULTS, settings);
    this.action = action ?? null;

    this.dialog = document.createElement('craft-dialog') as CpModalDialog;
    this.dialog.classList.add('cpmodal-dialog');
    if (this.#cpSettings.title) {
      this.dialog.label = this.#cpSettings.title;
    } else {
      this.dialog.noHeader = true;
    }

    // Fresh id per instance: container ids feed the X-Craft-Container-Id header.
    const containerAttributes = Object.assign(
      {id: `cp-modal-${Math.floor(Math.random() * 100000000)}`},
      this.#cpSettings.containerAttributes
    );
    // SAFETY: The container is a form unless a caller asks for another element.
    this.#container = document.createElement(
      this.#cpSettings.containerElement
    ) as HTMLFormElement;
    setElementAttributes(this.#container, containerAttributes);
    jq()?.(this.#container).data('cpModal', this);

    this.#content = document.createElement('div');
    this.#content.className = 'cpmodal-content';

    this.#loadSpinner = document.createElement(
      'craft-spinner'
    ) as HTMLElement & {
      hidden: boolean;
    };
    this.#loadSpinner.className = 'cpmodal-spinner';
    this.#loadSpinner.setAttribute('label', t('Loading'));
    this.#container.append(this.#content, this.#loadSpinner);

    this.#cancelBtn = this.#footerButton(t('Cancel'));

    if (this.#cpSettings.showSubmitButton) {
      this.#saveBtn = this.#footerButton(t('Save'));
      this.#saveBtn.setAttribute('variant', 'primary');
      this.#saveBtn.setAttribute('aria-keyshortcuts', commandShortcut('S'));
    }

    this.dialog.append(
      this.#container,
      this.#cancelBtn,
      ...(this.#saveBtn ? [this.#saveBtn] : [])
    );

    this.addListener(this.#cancelBtn, 'click', () => {
      this.closeMeMaybe();
    });
    if (this.#saveBtn) {
      this.addListener(this.#saveBtn, 'click', () => {
        this.#container.requestSubmit();
      });
    }
    this.addListener(this.#container, 'submit', 'handleSubmit');
    this.#shortcuts = registerShortcut('Mod+S', (ev) => this.handleSubmit(ev), {
      target: this.dialog,
      description: () => t('Save'),
      group: () => t('Forms'),
      isAvailable: () => this.visible,
    });
    this.dialog.addEventListener('craft-before-hide', (ev) => {
      if (ev.target !== this.dialog) return;
      ev.preventDefault();
      this.closeMeMaybe();
    });

    document.body.append(this.dialog);
    this.dialog.opened = true;
    this.visible = true;

    void this.load();
  }

  #footerButton(label: string): HTMLElement {
    const button = document.createElement('craft-button');
    button.setAttribute('type', 'button');
    button.setAttribute('slot', 'footer');
    button.textContent = label;
    return button;
  }

  load(data?: UiValues, refreshInitialData?: boolean): Promise<void> {
    return new Promise((resolve, reject) => {
      this.trigger('beforeLoad');
      this.showLoadSpinner();

      if (this.#abortController) {
        this.#ignoreFailedRequest = true;
        this.#abortController.abort();
      }

      this.#abortController = new AbortController();

      sendLegacyRequest(
        actionClient,
        Object.assign(
          {
            params: Object.assign(
              {},
              this.getParams(),
              this.#cpSettings.params
            ),
            signal: this.#abortController.signal,
            headers: {
              'X-Craft-Container-Id': this.#container.id,
            },
          },
          this.#cpSettings.requestOptions,
          {method: 'get', url: this.action}
        )
      )
        .then((response: any) => {
          this.update(response.data)
            .then(() => {
              if (refreshInitialData !== false) {
                this.#deltaNames = response.data.deltaNames ?? [];
                this.#initialDeltaValues =
                  response.data.initialDeltaValues ?? {};
                this.#initialSerializedValue = this.#serialize();
              }
              resolve();
            })
            .catch((e: Error) => {
              reject(e);
            });
        })
        .catch((e: Error) => {
          if (!this.#ignoreFailedRequest) {
            showError();
            reject(e);
          }
          this.#ignoreFailedRequest = false;
        })
        .finally(() => {
          this.hideLoadSpinner();
          this.#abortController = null;
        });
    });
  }

  getParams(): UiValues {
    return {};
  }

  showLoadSpinner(): void {
    this.#loadSpinner.hidden = false;
  }

  hideLoadSpinner(): void {
    this.#loadSpinner.hidden = true;
  }

  async update(data: any): Promise<void> {
    this.namespace = data.namespace;

    if (data.bodyClass) {
      this.#content.classList.add(
        ...String(data.bodyClass).split(/\s+/).filter(Boolean)
      );
    }

    // Runs any scripts in the content, as jQuery's `html()` did
    this.#content.replaceChildren();
    await appendElementHtml(data.content ?? '', this.#content);

    if (data.submitButtonLabel && this.#saveBtn) {
      this.#saveBtn.textContent = data.submitButtonLabel;
    }

    if (data.formAttributes) {
      setElementAttributes(this.#container, data.formAttributes);
    }

    return new Promise((resolve) => {
      requestAnimationFrame(() => {
        void appendHeadHtml(data.headHtml).then(() =>
          appendBodyHtml(data.bodyHtml)
        );

        initLegacyUi(this.#content);
        loadElementThumbs(this.#content);

        if (!isMobileBrowser()) {
          setFocusWithin(this.#content);
        }

        resolve();
        this.trigger('load');
      });
    });
  }

  showSubmitSpinner(): void {
    if (this.#saveBtn) {
      this.#saveBtn.loading = true;
    }
  }

  hideSubmitSpinner(): void {
    if (this.#saveBtn) {
      this.#saveBtn.loading = false;
    }
  }

  handleSubmit(ev: Event): void {
    ev.preventDefault();
    this.submit();
  }

  submit(): void {
    this.showSubmitSpinner();

    const data = findDeltaData(
      this.#initialSerializedValue ?? '',
      this.#serialize(),
      this.#deltaNames,
      null,
      this.#initialDeltaValues
    );

    actionClient
      .post(getCpUrl(), data, {
        headers: {
          'X-Craft-Namespace': this.namespace,
        },
      })
      .then((response: any) => {
        this.handleSubmitResponse(response);
      })
      .catch((error: CpModalRequestError) => {
        this.handleSubmitError(error);
      })
      .finally(() => {
        this.hideSubmitSpinner();
      });
  }

  handleSubmitResponse(response: any): void {
    this.clearErrors();
    const data = response.data || {};
    if (data.message) {
      showSuccess(data.message, data.notificationSettings);
    }
    if (data.modelClass && data.modelId) {
      void refreshComponentInstances(data.modelClass, data.modelId);
    }
    this.succeeded = true;
    const ev = {
      response,
      data: (data.modelName && data[data.modelName]) || {},
    };
    this.trigger('submit', ev);
    this.#cpSettings.onSubmit?.(ev);
    if (this.#cpSettings.closeOnSubmit) {
      this.close();
    }
  }

  handleSubmitError(error: CpModalRequestError): void {
    if (
      !isHttpError(error) ||
      !error.response ||
      ![400, 422].includes(error.response.status ?? 0)
    ) {
      showError();
      throw error;
    }

    const data = error.response.data || {};
    showError(data.message);
    if (data.errors) {
      this.showErrors(data.errors);
    }
  }

  showErrors(errors: Record<string, string[]>): void {
    this.clearErrors();

    Object.entries(errors).forEach(([name, fieldErrors]) => {
      const path = [
        ...(this.namespace ? [this.namespace] : []),
        ...name.split('.'),
      ];
      const fields = new Set<Element>(
        this.#container.querySelectorAll(
          `[data-attribute="${CSS.escape(name)}"], [data-error-key="${CSS.escape(name)}"]`
        )
      );
      for (const input of this.#container.querySelectorAll(
        `[name="${CSS.escape(inputName(path))}"]`
      )) {
        const field = input.closest('craft-field');
        if (field) {
          fields.add(field);
        }
      }
      if (fields.size) {
        const field = [...fields];
        addErrorsToField(field, fieldErrors);
        for (const el of field) {
          if (el.localName === 'craft-field') {
            el.querySelector(':scope > ul.errors')?.setAttribute(
              'slot',
              'feedback'
            );
          }
        }
        this.#fieldsWithErrors.push(field);
      }
    });
  }

  clearErrors(): void {
    this.#fieldsWithErrors.forEach(($field) => {
      clearErrorsFromField($field);
    });
    this.#fieldsWithErrors = [];
  }

  /** Legacy code can swap in its own serializer through jQuery's `data()`. */
  #serialize(): string {
    const serializer = jq()?.(this.#container).data('serializer');
    return typeof serializer === 'function'
      ? serializer()
      : serializeFormInputs(this.#container);
  }

  isDirty(): boolean {
    if (this.#initialSerializedValue === undefined) {
      return false;
    }

    return this.#initialSerializedValue !== this.#serialize();
  }

  closeMeMaybe(): void {
    if (!this.visible) {
      return;
    }

    if (
      !this.isDirty() ||
      confirm(
        t(
          'Are you sure you want to close this screen? Any changes will be lost.'
        )
      )
    ) {
      this.close();
    }
  }

  close(): void {
    if (!this.visible) {
      return;
    }
    this.visible = false;
    this.#shortcuts.unregister();

    if (this.#abortController) {
      this.#ignoreFailedRequest = true;
      this.#abortController.abort();
    }

    const dialog = this.dialog;
    dialog.addEventListener('craft-after-hide', () => dialog.remove(), {
      once: true,
    });
    dialog.opened = false;

    if (!this.succeeded) {
      this.trigger('cancel');
      this.#cpSettings.onCancel?.();
    }
    this.trigger('close');
    this.destroy();
  }

  /** @deprecated `close()` should be used instead. */
  hide(): void {
    this.close();
  }
}
