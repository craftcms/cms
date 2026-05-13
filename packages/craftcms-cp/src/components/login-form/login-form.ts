import {html, LitElement, nothing} from 'lit';
import {property, state, query} from 'lit/decorators.js';
import {
  browserSupportsWebAuthn,
  platformAuthenticatorIsAvailable,
  startAuthentication,
} from '@simplewebauthn/browser';
import {actionClient} from '@src/utilities/api/actionClient.js';
import {t} from '@src/utilities/translate.js';
import componentStyles from './login-form.styles.js';
import type {TwoFactorData} from './login-2fa.js';
import './login-2fa.js';
import './login-reset-password.js';
import '../button/button.js';
import '../input-password/input-password.js';

type View = 'login' | 'reset-password' | '2fa';

/**
 * @summary Full-page login form with password reset and passkey support.
 * @since 6.0
 *
 * @slot alternative-methods - Additional sign-in buttons (OAuth, SSO, etc.)
 *
 * @fires craft-login - When login succeeds. Detail: `{ returnUrl: string }`
 */
export default class CraftLoginForm extends LitElement {
  static override styles = [componentStyles];

  /** Show the passkey sign-in button if the platform supports it */
  @property({type: Boolean, attribute: 'show-passkey-btn'})
  showPasskeyBtn = true;

  /** Show the "Forgot password?" link and reset-password view */
  @property({type: Boolean, attribute: 'show-reset-password'})
  showResetPassword = false;

  /** Show the "Stay signed in" checkbox */
  @property({type: Boolean, attribute: 'show-remember-me'})
  showRememberMe = false;

  /** Pre-filled username / email value */
  @property() username = '';

  /** Locks the username field to a specific email (hidden input) */
  @property({attribute: 'static-email'}) staticEmail = '';

  /** Whether the site uses email-only login */
  @property({type: Boolean, attribute: 'use-email-as-username'})
  useEmailAsUsername = false;

  /** Minimum allowed password length */
  @property({type: Number, attribute: 'min-password-length'})
  minPasswordLength = 6;

  /** Maximum allowed password length */
  @property({type: Number, attribute: 'max-password-length'})
  maxPasswordLength = 160;

  /** "Remember me" label with duration baked in by the server */
  @property({attribute: 'remember-me-label'}) rememberMeLabel = '';

  /** Error message pre-rendered by the server */
  @property({attribute: 'initial-error'}) initialError = '';

  @state() private _view: View = 'login';
  @state() private _error = '';
  @state() private _loginBusy = false;
  @state() private _passkeyBusy = false;
  @state() private _canUsePasskey = false;
  @state() private _validateOnInput = false;
  @state() private _twoFactorData: TwoFactorData | null = null;
  @state() private _resetUsername = '';

  @query('.login-username') private _usernameInput?: HTMLInputElement;
  @query('craft-input-password.login-password')
  private _passwordInput?: HTMLElement & {value: string};
  @query('.login-remember-me') private _rememberMeInput?: HTMLInputElement;

  override async connectedCallback() {
    super.connectedCallback();

    if (this.initialError) this._error = this.initialError;

    if (this.showPasskeyBtn && browserSupportsWebAuthn()) {
      this._canUsePasskey = await platformAuthenticatorIsAvailable();
    }
  }

  private _usernameLabel() {
    return this.useEmailAsUsername ? t('Email') : t('Username or Email');
  }

  private _validate(): string | true {
    const username = this._usernameInput?.value ?? '';

    if (username.length === 0) {
      return this.useEmailAsUsername
        ? t('Invalid email.')
        : t('Invalid username or email.');
    }

    if (this.useEmailAsUsername && !username.match(/.+@.+\..+/)) {
      return t('Invalid email.');
    }

    const passwordLength = this._passwordInput?.value.length ?? 0;

    if (passwordLength < this.minPasswordLength) {
      return t(
        '{attribute} should contain at least {min, number} {min, plural, one{character} other{characters}}.',
        {attribute: t('Password'), min: this.minPasswordLength},
        'yii'
      );
    }

    if (passwordLength > this.maxPasswordLength) {
      return t(
        '{attribute} should contain at most {max, number} {max, plural, one{character} other{characters}}.',
        {attribute: t('Password'), max: this.maxPasswordLength},
        'yii'
      );
    }

    return true;
  }

  private _onInput() {
    if (this._validateOnInput && this._validate() === true) {
      this._error = '';
    }
  }

  private async _onSubmit(event: Event) {
    event.preventDefault();

    const error = this._validate();
    if (error !== true) {
      this._setError(error);
      this._validateOnInput = true;
      return;
    }

    this._error = '';
    this._loginBusy = true;

    try {
      const {data} = await actionClient.post('users/login', {
        loginName: this._usernameInput!.value,
        password: this._passwordInput!.value,
        rememberMe: this._rememberMeInput?.checked ? '1' : '',
      });

      if (data.authMethod) {
        this._loginBusy = false;
        this._twoFactorData = data;
        this._view = '2fa';
      } else {
        this._redirect(data.returnUrl);
      }
    } catch (e: any) {
      this._loginBusy = false;
      this._setError(e?.response?.data?.message ?? t('A server error occurred.'));
    }
  }

  private async _loginWithPasskey() {
    if (this._passkeyBusy) return;

    this._error = '';
    this._passkeyBusy = true;

    try {
      const {data: optionsData} = await actionClient.post(
        'auth/passkey-request-options'
      );
      const authResponse = await startAuthentication({
        optionsJSON: JSON.parse(optionsData.options),
      });
      const {data} = await actionClient.post('users/login-with-passkey', {
        requestOptions: optionsData.options,
        response: JSON.stringify(authResponse),
      });

      this._redirect(data.returnUrl);
    } catch (e: any) {
      this._passkeyBusy = false;
      const message = e?.response?.data?.message;
      if (message) {
        this._setError(message);
      } else {
        console.warn(e);
      }
    }
  }

  private _showResetPasswordForm() {
    this._error = '';
    this._resetUsername = this._usernameInput?.value ?? '';
    this._view = 'reset-password';
  }

  private _onResetBack(event: CustomEvent) {
    const username = (event.detail?.username as string) ?? '';
    this._view = 'login';
    this.updateComplete.then(() => {
      if (username && this._usernameInput) this._usernameInput.value = username;
      this._usernameInput?.focus();
    });
  }

  private _onLoginSuccess(event: CustomEvent) {
    this._redirect((event.detail as {returnUrl: string}).returnUrl);
  }

  private _onLoginError(event: CustomEvent) {
    this._setError((event.detail as {message: string}).message);
  }

  private _setError(message: string) {
    this._error = message;
    const live = this.shadowRoot?.querySelector(
      '.visually-hidden[role="status"]'
    );
    if (live) live.textContent = message;
  }

  private _redirect(returnUrl: string) {
    this.dispatchEvent(
      new CustomEvent('craft-login', {
        bubbles: true,
        composed: true,
        detail: {returnUrl},
      })
    );
    window.location.href = returnUrl;
  }

  override render() {
    return html`
      <div class="login-container">
        <span
          class="visually-hidden"
          role="status"
          aria-live="polite"
          aria-atomic="true"
        ></span>

        ${this._view === 'login'
          ? this._renderLoginView()
          : this._view === 'reset-password'
            ? html`
                <craft-login-reset-password
                  ?use-email-as-username="${this.useEmailAsUsername}"
                  username="${this._resetUsername}"
                  @reset-back="${this._onResetBack}"
                ></craft-login-reset-password>
              `
            : html`
                <craft-login-2fa
                  .data="${this._twoFactorData!}"
                  @login-success="${this._onLoginSuccess}"
                  @login-error="${this._onLoginError}"
                ></craft-login-2fa>
              `}
      </div>
    `;
  }

  private _renderLoginView() {
    const hasAltMethods =
      this._canUsePasskey || this.querySelector('[slot="alternative-methods"]');

    return html`
      <div class="login-form-container pane secondary">
        <form
          class="login-form"
          method="post"
          accept-charset="UTF-8"
          @submit="${this._onSubmit}"
        >
          ${this.staticEmail
            ? html`<input
                type="hidden"
                class="login-username"
                name="username"
                .value="${this.staticEmail}"
              />`
            : html`
                <div class="field">
                  <label for="login-username">${this._usernameLabel()}</label>
                  <input
                    id="login-username"
                    type="${this.useEmailAsUsername ? 'email' : 'text'}"
                    class="login-username"
                    name="username"
                    .value="${this.username}"
                    autocomplete="username"
                    autocapitalize="off"
                    aria-required="true"
                    @input="${this._onInput}"
                  />
                </div>
              `}

          <div class="field">
            <label for="login-password">${t('Password')}</label>
            <craft-input-password
              id="login-password"
              class="login-password"
              name="password"
              autocomplete="current-password"
              aria-required="true"
              @input="${this._onInput}"
            ></craft-input-password>
          </div>

          ${this.showResetPassword
            ? html`
                <div class="forgot-password-row">
                  <button
                    type="button"
                    class="login-forgot-password"
                    @click="${this._showResetPasswordForm}"
                  >
                    ${t('Forgot password?')}
                  </button>
                </div>
              `
            : nothing}

          ${this.showRememberMe
            ? html`
                <div class="remember-me-row">
                  <input
                    type="checkbox"
                    id="login-remember-me"
                    class="login-remember-me"
                  />
                  <label for="login-remember-me">
                    ${this.rememberMeLabel || t('Stay signed in')}
                  </label>
                </div>
              `
            : nothing}

          <craft-button
            type="submit"
            variant="primary"
            ?loading="${this._loginBusy}"
          >
            ${t('Sign in')}
          </craft-button>
        </form>

        ${this._error
          ? html`<p class="login-errors">${this._error}</p>`
          : nothing}
      </div>

      ${hasAltMethods
        ? html`
            <div class="alternative-login-methods">
              ${this._canUsePasskey
                ? html`
                    <craft-button
                      type="button"
                      appearance="filled"
                      ?loading="${this._passkeyBusy}"
                      @click="${this._loginWithPasskey}"
                    >
                      ${t('Sign in with a passkey')}
                    </craft-button>
                  `
                : nothing}
              <slot name="alternative-methods"></slot>
            </div>
          `
        : nothing}
    `;
  }
}

if (!customElements.get('craft-login-form')) {
  customElements.define('craft-login-form', CraftLoginForm);
}

declare global {
  interface HTMLElementTagNameMap {
    'craft-login-form': CraftLoginForm;
  }
}
