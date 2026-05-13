import {html, LitElement, nothing} from 'lit';
import {property, state, query} from 'lit/decorators.js';
import {unsafeHTML} from 'lit/directives/unsafe-html.js';
import {actionClient} from '@src/utilities/api/actionClient.js';
import {t} from '@src/utilities/translate.js';
import componentStyles from './login-form.styles.js';
import '../button/button.js';
import '../spinner/spinner.js';

export interface TwoFactorData {
  authForm: string;
  headHtml: string;
  bodyHtml: string;
  returnUrl: string;
  authMethod: string;
  otherMethods: Array<{name: string; class: string}>;
}

declare const Craft: {
  appendHeadHtml: (html: string) => Promise<void>;
  appendBodyHtml: (html: string) => Promise<void>;
  initUiElements: (container: Element) => void;
  createAuthFormHandler: (
    method: string,
    container: Element,
    onSuccess: () => void,
    onError: (error: string) => void
  ) => void;
};

/**
 * @summary Renders and initialises a server-side 2FA form, and handles
 * switching between authentication methods.
 * @since 6.0
 *
 * @fires login-success - When authentication succeeds. Detail: `{ returnUrl: string }`
 * @fires login-error   - When authentication fails.  Detail: `{ message: string }`
 */
export default class CraftLogin2fa extends LitElement {
  static override styles = [componentStyles];

  @property({attribute: false}) data!: TwoFactorData;

  @state() private _switching = false;

  @query('.auth-form-container') private _container?: HTMLElement;

  #initialized = false;

  override async updated(changed: Map<string, unknown>) {
    super.updated(changed);

    if (!this.#initialized && !this._switching && this._container) {
      this.#initialized = true;
      await this.#initForm();
    }
  }

  async #initForm() {
    if (!this._container) return;

    await Craft.appendHeadHtml(this.data.headHtml);
    await Craft.appendBodyHtml(this.data.bodyHtml);
    Craft.initUiElements(this._container);

    Craft.createAuthFormHandler(
      this.data.authMethod,
      this._container,
      () => {
        this.dispatchEvent(
          new CustomEvent('login-success', {
            bubbles: true,
            composed: true,
            detail: {returnUrl: this.data.returnUrl},
          })
        );
      },
      (message) => {
        this.dispatchEvent(
          new CustomEvent('login-error', {
            bubbles: true,
            composed: true,
            detail: {message},
          })
        );
      }
    );

    this._container
      .querySelector<HTMLElement>(':focus-visible, input, button')
      ?.focus();
  }

  async #switchMethod(methodClass: string) {
    this._switching = true;
    this.#initialized = false;

    try {
      const {data} = await actionClient.post('users/auth-form', {
        method: methodClass,
      });
      this.data = data;
    } finally {
      this._switching = false;
    }
  }

  override render() {
    if (this._switching) {
      return html`
        <div class="login-form-container pane secondary">
          <div class="spinner-overlay">
            <craft-spinner></craft-spinner>
          </div>
        </div>
      `;
    }

    return html`
      <div class="login-form-container pane secondary">
        <div class="auth-form-container">${unsafeHTML(this.data.authForm)}</div>

        ${this.data.otherMethods.length
          ? html`
              <hr />
              <div class="login-alt-container">
                <p>${t('Try another way')}</p>
                <div class="login-alt-menu">
                  ${this.data.otherMethods.map(
                    (method) => html`
                      <craft-button
                        type="button"
                        appearance="plain"
                        @click="${() => this.#switchMethod(method.class)}"
                      >
                        ${method.name}
                      </craft-button>
                    `
                  )}
                </div>
              </div>
            `
          : nothing}
      </div>
    `;
  }
}

if (!customElements.get('craft-login-2fa')) {
  customElements.define('craft-login-2fa', CraftLogin2fa);
}

declare global {
  interface HTMLElementTagNameMap {
    'craft-login-2fa': CraftLogin2fa;
  }
}
