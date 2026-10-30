import {Base, isMobileBrowser} from '@craftcms/garnish';
import {inputName} from '@/modules/ui/runtime';
import type {UiValues} from '@/modules/ui/types';
import {isHttpError} from '@craftcms/ui/utilities/api/http';
import type {LegacyRequestOptions} from '@craftcms/ui/utilities/api/legacyRequest';
import {commandShortcut} from '@/common/utils/keyShortcuts';

declare const Craft: any;
declare const $: any;

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
 * Like its sibling {@link CpScreenSlideout}, it keeps the jQuery seam
 * (`declare const $`) for the form, because the delta-tracking data
 * (`.data('initialSerializedValue' | 'delta-names' | 'serializer')`) and the
 * `Craft.ui.*` field-error helpers share those jQuery conventions with the rest
 * of the CP-screen framework.
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
  #fieldsWithErrors: unknown[] = [];

  // jQuery refs (form-ops seam), mirroring the legacy `$`-prefixed fields.
  #$container: any = null;
  #$content: any = null;
  #$loadSpinner: any = null;
  #$cancelBtn: any = null;
  #$saveBtn: any = null;

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
    this.#$container = $(
      `<${this.#cpSettings.containerElement}/>`,
      containerAttributes
    );
    this.#$container.data('cpModal', this);

    this.#$content = $('<div/>', {class: 'cpmodal-content'}).appendTo(
      this.#$container
    );

    this.#$loadSpinner = $('<craft-spinner/>', {
      class: 'cpmodal-spinner',
      label: Craft.t('app', 'Loading'),
    }).appendTo(this.#$container);

    this.#$cancelBtn = $('<craft-button/>', {
      type: 'button',
      slot: 'footer',
      text: Craft.t('app', 'Cancel'),
    });

    if (this.#cpSettings.showSubmitButton) {
      this.#$saveBtn = $('<craft-button/>', {
        type: 'button',
        slot: 'footer',
        variant: 'primary',
        text: Craft.t('app', 'Save'),
        'aria-keyshortcuts': commandShortcut('S'),
      });
    }

    this.dialog.append(
      this.#$container[0],
      this.#$cancelBtn[0],
      ...(this.#$saveBtn ? [this.#$saveBtn[0]] : [])
    );

    this.addListener(this.#$cancelBtn[0], 'click', () => {
      this.closeMeMaybe();
    });
    if (this.#$saveBtn) {
      this.addListener(this.#$saveBtn[0], 'click', () => {
        this.#$container[0].requestSubmit();
      });
    }
    this.addListener(this.#$container[0], 'submit', 'handleSubmit');
    this.dialog.addEventListener('keydown', (ev) => {
      if ((ev.metaKey || ev.ctrlKey) && ev.key.toLowerCase() === 's') {
        this.handleSubmit(ev);
      }
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

  load(data?: UiValues, refreshInitialData?: boolean): Promise<void> {
    return new Promise((resolve, reject) => {
      this.trigger('beforeLoad');
      this.showLoadSpinner();

      if (this.#abortController) {
        this.#ignoreFailedRequest = true;
        this.#abortController.abort();
      }

      this.#abortController = new AbortController();

      Craft.sendActionRequest(
        'GET',
        this.action,
        $.extend(
          {
            params: Object.assign(
              {},
              this.getParams(),
              this.#cpSettings.params
            ),
            signal: this.#abortController.signal,
            headers: {
              'X-Craft-Container-Id': this.#$container.attr('id'),
            },
          },
          this.#cpSettings.requestOptions
        )
      )
        .then((response: any) => {
          this.update(response.data)
            .then(() => {
              if (refreshInitialData !== false) {
                this.#$container.data('delta-names', response.data.deltaNames);
                this.#$container.data(
                  'initial-delta-values',
                  response.data.initialDeltaValues
                );
                this.#$container.data(
                  'initialSerializedValue',
                  this.#$container.serialize()
                );
              }
              resolve();
            })
            .catch((e: Error) => {
              reject(e);
            });
        })
        .catch((e: Error) => {
          if (!this.#ignoreFailedRequest) {
            Craft.cp.displayError();
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
    this.#$loadSpinner.prop('hidden', false);
  }

  hideLoadSpinner(): void {
    this.#$loadSpinner.prop('hidden', true);
  }

  update(data: any): Promise<void> {
    return new Promise((resolve) => {
      this.namespace = data.namespace;

      if (data.bodyClass) {
        this.#$content.addClass(data.bodyClass);
      }

      this.#$content.html(data.content);

      if (data.submitButtonLabel) {
        this.#$saveBtn?.text(data.submitButtonLabel);
      }

      if (data.formAttributes) {
        Craft.setElementAttributes(this.#$container, data.formAttributes);
      }

      requestAnimationFrame(() => {
        Craft.appendHeadHtml(data.headHtml);
        Craft.appendBodyHtml(data.bodyHtml);

        Craft.initUiElements(this.#$content);
        Craft.cp.elementThumbLoader.load(this.#$content);

        if (!isMobileBrowser()) {
          Craft.setFocusWithin(this.#$content);
        }

        resolve();
        this.trigger('load');
      });
    });
  }

  showSubmitSpinner(): void {
    this.#$saveBtn?.prop('loading', true);
  }

  hideSubmitSpinner(): void {
    this.#$saveBtn?.prop('loading', false);
  }

  handleSubmit(ev: Event): void {
    ev.preventDefault();
    this.submit();
  }

  submit(): void {
    this.showSubmitSpinner();

    const data = Craft.findDeltaData(
      this.#$container.data('initialSerializedValue'),
      this.#$container.serialize(),
      this.#$container.data('delta-names'),
      null,
      this.#$container.data('initial-delta-values')
    );

    Craft.sendActionRequest('POST', null, {
      data,
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
      Craft.cp.displaySuccess(data.message, data.notificationSettings);
    }
    if (data.modelClass && data.modelId) {
      Craft.refreshComponentInstances(data.modelClass, data.modelId);
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
      Craft.cp.displayError();
      throw error;
    }

    const data = error.response.data || {};
    Craft.cp.displayError(data.message);
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
      const $field = this.#$container
        .find(
          `[data-attribute="${CSS.escape(name)}"], [data-error-key="${CSS.escape(name)}"]`
        )
        .add(
          this.#$container
            .find(`[name="${CSS.escape(inputName(path))}"]`)
            .closest('craft-field')
        );
      if ($field.length) {
        Craft.ui.addErrorsToField($field, fieldErrors);
        $field
          .filter('craft-field')
          .children('ul.errors')
          .attr('slot', 'feedback');
        this.#fieldsWithErrors.push($field);
      }
    });
  }

  clearErrors(): void {
    this.#fieldsWithErrors.forEach(($field) => {
      Craft.ui.clearErrorsFromField($field);
    });
    this.#fieldsWithErrors = [];
  }

  isDirty(): boolean {
    const initialValue = this.#$container.data('initialSerializedValue');
    if (initialValue === undefined) {
      return false;
    }

    const serializer =
      this.#$container.data('serializer') ||
      (() => this.#$container.serialize());
    return initialValue !== serializer();
  }

  closeMeMaybe(): void {
    if (!this.visible) {
      return;
    }

    if (
      !this.isDirty() ||
      confirm(
        Craft.t(
          'app',
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
