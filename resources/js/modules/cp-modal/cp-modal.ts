import {Modal, ESC_KEY, S_KEY, isMobileBrowser} from '@craftcms/garnish';
import {uiLayerManager} from '@/modules/slideout/slideout';
import type {FormValues} from '@/modules/forms/types';
import type {AxiosRequestConfig} from 'axios';

declare const Craft: any;
declare const $: any;
declare const axios: any;

interface CpModalSettings {
  params: FormValues;
  containerElement: string;
  containerAttributes: Record<string, string>;
  requestOptions: AxiosRequestConfig;
  closeOnSubmit: boolean;
  showSubmitButton: boolean;
  onSubmit: (event?: CpModalSubmitEvent) => void;
  onCancel: () => void;
}

interface CpModalSubmitEvent {
  response: {data?: FormValues};
  data: FormValues;
}

interface CpModalRequestError extends Error {
  isAxiosError?: boolean;
  response?: {
    status?: number;
    data?: {message?: string; errors?: Record<string, string[]>};
  };
}

// Module-level (not a `static defaults`, which would collide with the base
// `Modal.defaults`, a differently-shaped `ModalSettings`).
const DEFAULTS: CpModalSettings = {
  params: {},
  containerElement: 'form',
  containerAttributes: {
    action: '',
    method: 'post',
    novalidate: '',
    class: 'cpmodal modal fitted',
  },
  requestOptions: {},
  closeOnSubmit: true,
  showSubmitButton: true,
  onSubmit: () => {},
  onCancel: () => {},
};

/**
 * CpModal — a port of `Craft.CpModal` onto the modern `@craftcms/garnish`
 * `Modal`. Loads a control-panel screen (`action`) into a modal form, then
 * submits it with delta-encoded data and renders validation errors inline.
 * Booted from PHP deletion blockers (`new Craft.CpModal('…', {…})`).
 *
 * The modal shell is modern (`extends Modal`), but — like its sibling
 * {@link CpScreenSlideout} — it keeps the jQuery seam (`declare const $`) for
 * the container/form operations, because the delta-tracking data
 * (`.data('initialSerializedValue' | 'delta-names' | 'serializer')`) and the
 * `Craft.ui.*` field-error helpers share those jQuery conventions with the rest
 * of the CP-screen framework. `Craft`/`axios` stay page globals; `uiLayerManager`
 * comes from the slideout module.
 */
export class CpModal extends Modal {
  action: string | null = null;
  namespace: string | null = null;
  succeeded = false;
  resizeObserver: ResizeObserver | null = null;

  #cpSettings: CpModalSettings;
  #cancelToken: {cancel(): void; token: unknown} | null = null;
  #ignoreFailedRequest = false;
  #fieldsWithErrors: unknown[] = [];

  // jQuery refs (form-ops seam), mirroring the legacy `$`-prefixed fields.
  #$container: any = null;
  #$body: any = null;
  #$content: any = null;
  #$footer: any = null;
  #$loadSpinner: any = null;
  #$cancelBtn: any = null;
  #$saveBtn: any = null;

  constructor(action?: string, settings?: Partial<CpModalSettings>) {
    // The parent Modal must init first (TS: no `this` before super), so it's
    // constructed container-less and non-auto-showing; shade/esc closing is
    // handled by CpModal itself (closeMeMaybe), so disable the built-ins.
    super(undefined, {
      autoShow: false,
      hideOnShadeClick: false,
      hideOnEsc: false,
    });

    this.#cpSettings = Object.assign({}, DEFAULTS, settings);
    this.action = action ?? null;
    this.#fieldsWithErrors = [];

    // Body / content
    this.#$body = $('<div/>', {class: 'cpmodal-body'});
    this.#$content = $('<div/>', {class: 'cpmodal-content'}).appendTo(
      this.#$body
    );

    // Footer
    this.#$footer = $('<div/>', {class: 'cpmodal-footer hidden'});
    $('<div/>', {class: 'cp:flex-grow'}).appendTo(this.#$footer);
    const $btnContainer = $('<div/>', {
      class: 'cp:flex cp:flex-nowrap',
    }).appendTo(this.#$footer);

    this.#$loadSpinner = $('<div/>', {
      class: 'spinner',
      title: Craft.t('app', 'Loading'),
      'aria-label': Craft.t('app', 'Loading'),
    }).prependTo($btnContainer);

    this.#$cancelBtn = $('<button/>', {
      type: 'button',
      class: 'btn',
      text: Craft.t('app', 'Cancel'),
    }).appendTo($btnContainer);

    if (this.#cpSettings.showSubmitButton) {
      this.#$saveBtn = Craft.ui
        .createSubmitButton({
          label: Craft.t('app', 'Save'),
          spinner: true,
        })
        .appendTo($btnContainer);
    }

    // Container form — fresh id per instance (the legacy shared-id default was a
    // latent bug: container ids feed the X-Craft-Container-Id header).
    const containerAttributes = Object.assign(
      {id: `cp-modal-${Math.floor(Math.random() * 100000000)}`},
      this.#cpSettings.containerAttributes
    );
    this.#$container = $(
      `<${this.#cpSettings.containerElement}/>`,
      containerAttributes
    );
    this.#$container.append(this.#$body.add(this.#$footer));

    this.setContainer(this.#$container[0]);
    this.#$container.data('cpModal', this);

    // Shortcuts + events
    uiLayerManager().registerShortcut(
      {keyCode: S_KEY, ctrl: true},
      (ev: Event) => {
        this.handleSubmit(ev);
      }
    );
    uiLayerManager().registerShortcut(ESC_KEY, () => {
      this.closeMeMaybe();
    });

    this.addListener(this.#$cancelBtn[0], 'click', () => {
      this.closeMeMaybe();
    });
    this.addListener(this.$shade, 'click', () => {
      this.closeMeMaybe();
    });
    this.addListener(this.#$container[0], 'submit', 'handleSubmit');

    this.resizeObserver = new ResizeObserver(() => {
      if (this.visible) {
        this.updateSizeAndPosition();
      }
    });

    this.load();
  }

  load(data?: FormValues, refreshInitialData?: boolean): Promise<void> {
    return new Promise((resolve, reject) => {
      this.trigger('beforeLoad');
      this.showLoadSpinner();

      if (this.#cancelToken) {
        this.#ignoreFailedRequest = true;
        this.#cancelToken.cancel();
      }

      this.#cancelToken = axios.CancelToken.source();

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
            cancelToken: this.#cancelToken?.token,
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
          this.show();
          this.#cancelToken = null;
        });
    });
  }

  getParams(): FormValues {
    return {};
  }

  showLoadSpinner(): void {
    this.#$loadSpinner.removeClass('hidden');
  }

  hideLoadSpinner(): void {
    this.#$loadSpinner.addClass('hidden');
  }

  update(data: any): Promise<void> {
    return new Promise((resolve) => {
      this.namespace = data.namespace;

      if (data.bodyClass) {
        this.#$body.addClass(data.bodyClass);
      }

      this.#$content.html(data.content);

      if (data.submitButtonLabel) {
        this.#$saveBtn?.text(data.submitButtonLabel);
      }

      if (data.formAttributes) {
        Craft.setElementAttributes(this.#$container, data.formAttributes);
      }

      this.#$footer.removeClass('hidden');

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
    this.#$saveBtn?.addClass('loading');
  }

  hideSubmitSpinner(): void {
    this.#$saveBtn?.removeClass('loading');
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
      !error.isAxiosError ||
      !error.response ||
      error.response.status !== 400
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
      const $field = this.#$container.find(`[data-attribute="${name}"]`);
      if ($field.length) {
        Craft.ui.addErrorsToField($field, fieldErrors);
        this.#fieldsWithErrors.push($field);
      }
    });
    this.updateSizeAndPosition();
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
    if (this.#cancelToken) {
      this.#ignoreFailedRequest = true;
      this.#cancelToken.cancel();
    }

    this.hide();
    this.trigger('close');
    this.destroy();
  }

  override onShow(): void {
    super.onShow();
    this.resizeObserver?.observe(this.#$body[0]);
  }

  override onHide(): void {
    super.onHide();
    this.resizeObserver?.disconnect();
    if (!this.succeeded) {
      this.trigger('cancel');
      this.#cpSettings.onCancel?.();
    }
  }
}
