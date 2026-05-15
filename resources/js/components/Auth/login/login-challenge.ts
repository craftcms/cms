import {html, LitElement, nothing} from 'lit';
import {property, query, state} from 'lit/decorators.js';
import {unsafeHTML} from 'lit/directives/unsafe-html.js';
import {ConfigService, t} from '@craftcms/cp';
import componentStyles from './login-form.styles.js';
import CraftTotpForm from '../totp/totp-form.js';
import CraftRecoveryCodeForm from '../recovery-codes/recovery-code-form.js';

export interface TwoFactorData {
  authForm: string;
  headHtml: string;
  bodyHtml: string;
  returnUrl: string;
  authMethod: string;
  otherMethods: Array<{name: string; handle: string; url: string}>;
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
const NATIVE_AUTH_METHODS = new Set([
  CraftTotpForm.METHOD,
  CraftRecoveryCodeForm.METHOD,
]);

/**
 * @summary Renders and initialises a 2FA form, and handles switching between
 * authentication methods.
 * @since 6.0
 *
 * @fires login-verified - When authentication succeeds (internal). Detail: `{ returnUrl: string }`
 * @fires login-failed   - When authentication fails (internal).   Detail: `{ message: string }`
 */
export default class CraftLoginChallenge extends LitElement {
  static override styles = [componentStyles];

  @property({attribute: false}) data!: TwoFactorData;

  @state() private _switching = false;

  @query('.auth-form-container') private _container?: HTMLElement;

  #initialized = false;
  #config = ConfigService.getInstance();

  override async updated(changed: Map<string, unknown>) {
    super.updated(changed);

    const isNativeMethod = NATIVE_AUTH_METHODS.has(this.data?.authMethod);

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
    if (!this._container) {
      return;
    }

    await Craft.appendHeadHtml(this.data.headHtml);
    await Craft.appendBodyHtml(this.data.bodyHtml);
    Craft.initUiElements(this._container);

    Craft.createAuthFormHandler(
      this.data.authMethod,
      this._container,
      () => {
        this.dispatchEvent(
          new CustomEvent('login-verified', {
            bubbles: true,
            composed: true,
            detail: {returnUrl: this.data.returnUrl},
          })
        );
      },
      (message) => {
        this.dispatchEvent(
          new CustomEvent('login-failed', {
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

  async #switchMethod(method: {name: string; handle: string; url: string}) {
    this._switching = true;
    this.#initialized = false;

    try {
      const response = await fetch(method.url, {
        headers: {
          Accept: 'application/json',
          'Content-Type': 'application/json',
        },
      });

      if (!response.ok) {
        throw new Error('Failed to fetch challenge data.');
      }

      this.data = await response.json();
    } finally {
      this._switching = false;
    }
  }

  override render() {
    if (this._switching) {
      return html`
        <craft-pane>
          <div class="spinner-overlay">
            <craft-spinner></craft-spinner>
          </div>
        </craft-pane>
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
                        @click="${() => this.#switchMethod(method)}"
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

if (!customElements.get('craft-login-challenge')) {
  customElements.define('craft-login-challenge', CraftLoginChallenge);
}

declare global {
  interface HTMLElementTagNameMap {
    'craft-login-challenge': CraftLoginChallenge;
  }
}
