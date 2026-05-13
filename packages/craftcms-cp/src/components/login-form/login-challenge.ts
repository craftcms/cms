import {html, LitElement, nothing} from 'lit';
import {property, query, state} from 'lit/decorators.js';
import {unsafeHTML} from 'lit/directives/unsafe-html.js';
import {actionClient, t} from '@src/index.js';
import componentStyles from './login-form.styles.js';
import CraftTotpForm from './totp-form.js';
import CraftRecoveryCodeForm from './recovery-code-form.js';
import './totp-form.js';
import './recovery-code-form.js';

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

/** Auth methods that have native web component implementations. */
const WEB_COMPONENT_METHODS: Record<string, string> = {
  [CraftTotpForm.METHOD]: 'craft-totp-form',
  [CraftRecoveryCodeForm.METHOD]: 'craft-recovery-code-form',
};

/**
 * @summary Renders and initialises a 2FA form, and handles switching between
 * authentication methods.
 * @since 6.0
 *
 * @fires craft:login:success - When authentication succeeds. Detail: `{ returnUrl: string }`
 * @fires craft:login:error   - When authentication fails.  Detail: `{ message: string }`
 */
export default class CraftLogin2fa extends LitElement {
  static override styles = [componentStyles];

  @property({attribute: false}) data!: TwoFactorData;

  @state() private _switching = false;

  @query('.auth-form-container') private _container?: HTMLElement;

  #initialized = false;

  override async updated(changed: Map<string, unknown>) {
    super.updated(changed);

    const isNativeMethod = !!WEB_COMPONENT_METHODS[this.data?.authMethod];

    if (
      !isNativeMethod &&
      !this.#initialized &&
      !this._switching &&
      this._container
    ) {
      this.#initialized = true;
      await this.#initLegacyForm();
    }
  }

  async #initLegacyForm() {
    if (!this._container) return;

    await Craft.appendHeadHtml(this.data.headHtml);
    await Craft.appendBodyHtml(this.data.bodyHtml);
    Craft.initUiElements(this._container);

    Craft.createAuthFormHandler(
      this.data.authMethod,
      this._container,
      () => {
        this.dispatchEvent(
          new CustomEvent('craft:login:success', {
            bubbles: true,
            composed: true,
            detail: {returnUrl: this.data.returnUrl},
          })
        );
      },
      (message) => {
        this.dispatchEvent(
          new CustomEvent('craft:login:error', {
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
      <craft-pane>
        <div class="auth-form-container">${unsafeHTML(this.data.authForm)}</div>
        ${this.data.otherMethods.length
          ? html`
              <hr />
              <craft-action-menu>
                <craft-button slot="invoker" appearance="plain" size="zero">
                  <craft-icon slot="prefix" name="chevron-down"></craft-icon>
                  ${t('Try another way')}
                </craft-button>

                <div slot="content">
                  ${this.data.otherMethods.map(
                    (method) => html`
                      <craft-action-item
                        @click="${() => this.#switchMethod(method.class)}"
                      >
                        ${method.name}
                      </craft-action-item>
                    `
                  )}
                </div>
              </craft-action-menu>
            `
          : nothing}
      </craft-pane>
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
