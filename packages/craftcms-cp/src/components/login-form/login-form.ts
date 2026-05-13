import {html, LitElement, nothing} from 'lit';
import {property, state, query} from 'lit/decorators.js';
import {unsafeHTML} from 'lit/directives/unsafe-html.js';
import {
  browserSupportsWebAuthn,
  platformAuthenticatorIsAvailable,
  startAuthentication,
} from '@simplewebauthn/browser';
import {actionClient} from '@src/utilities/api/actionClient.js';
import {t} from '@src/utilities/translate.js';
import componentStyles from './login-form.styles.js';
import '../button/button.js';
import '../dialog/dialog.js';
import '../spinner/spinner.js';
import '../input-password/input-password.js';

type View = 'login' | 'reset-password' | '2fa';

interface TwoFactorData {
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
 * @summary Full-page login form with password reset and passkey support.
 * @since 6.0
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

  /** "Remember me" label rendered with the duration baked in */
  @property({attribute: 'remember-me-label'}) rememberMeLabel = '';

  /** Error message pre-rendered by the server (e.g. after a failed redirect) */
  @property({attribute: 'initial-error'}) initialError = '';

  @state() private _view: View = 'login';
  @state() private _error = '';
  @state() private _loginBusy = false;
  @state() private _passkeyBusy = false;
  @state() private _resetBusy = false;
  @state() private _canUsePasskey = false;
  @state() private _validateOnInput = false;
  @state() private _resetValidateOnInput = false;
  @state() private _twoFactorData: TwoFactorData | null = null;
  @state() private _switchingMethod = false;

  @query('.login-username') private _usernameInput?: HTMLInputElement;
  @query('craft-input-password.login-password')
  private _passwordInput?: HTMLElement & {value: string};
  @query('.login-remember-me') private _rememberMeInput?: HTMLInputElement;
  @query('.reset-username') private _resetUsernameInput?: HTMLInputElement;
  @query('.auth-form-container') private _authFormContainer?: HTMLElement;

  private _2faInitialized = false;

  override async connectedCallback() {
    super.connectedCallback();

    if (this.initialError) {
      this._error = this.initialError;
    }

    if (this.showPasskeyBtn && browserSupportsWebAuthn()) {
      this._canUsePasskey = await platformAuthenticatorIsAvailable();
    }
  }

  override async updated(changed: Map<string, unknown>) {
    super.updated(changed);

    if (
      this._view === '2fa' &&
      this._twoFactorData &&
      !this._2faInitialized &&
      this._authFormContainer
    ) {
      this._2faInitialized = true;
      await this._init2faForm(this._twoFactorData);
    }
  }

  private _usernameLabel() {
    return this.useEmailAsUsername
      ? t('Email')
      : t('Username or Email');
  }

  private _validateLogin(): string | true {
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

  private _validateReset(): string | true {
    const username = this._resetUsernameInput?.value ?? '';

    if (username.length === 0) {
      return this.useEmailAsUsername
        ? t('Invalid email.')
        : t('Invalid username or email.');
    }

    if (this.useEmailAsUsername && !username.match(/.+@.+\..+/)) {
      return t('Invalid email.');
    }

    return true;
  }

  private _showError(message: string) {
    this._error = message;
    this._announce(message);
  }

  private _clearError() {
    this._error = '';
  }

  private _announce(message: string) {
    const live = this.shadowRoot?.querySelector('.visually-hidden[role="status"]');
    if (live) live.textContent = message;
  }

  private _onLoginInput() {
    if (this._validateOnInput && this._validateLogin() === true) {
      this._clearError();
    }
  }

  private async _onLoginSubmit(event: Event) {
    event.preventDefault();

    const error = this._validateLogin();
    if (error !== true) {
      this._showError(error);
      this._validateOnInput = true;
      return;
    }

    this._clearError();
    this._loginBusy = true;

    try {
      const {data} = await actionClient.post('users/login', {
        loginName: this._usernameInput!.value,
        password: this._passwordInput!.value,
        rememberMe: this._rememberMeInput?.checked ? '1' : '',
      });

      if (data.authMethod) {
        this._loginBusy = false;
        this._show2faForm(data);
      } else {
        this._handleLoginSuccess(data.returnUrl);
      }
    } catch (e: any) {
      this._loginBusy = false;
      this._showError(
        e?.response?.data?.message ?? t('A server error occurred.')
      );
    }
  }

  private _show2faForm(data: TwoFactorData) {
    this._clearError();
    this._2faInitialized = false;
    this._twoFactorData = data;
    this._view = '2fa';
    this._switchingMethod = false;
  }

  private async _init2faForm(data: TwoFactorData) {
    if (!this._authFormContainer) return;

    await Craft.appendHeadHtml(data.headHtml);
    await Craft.appendBodyHtml(data.bodyHtml);
    Craft.initUiElements(this._authFormContainer);

    Craft.createAuthFormHandler(
      data.authMethod,
      this._authFormContainer,
      () => this._handleLoginSuccess(data.returnUrl),
      (error) => this._showError(error)
    );

    this._authFormContainer.querySelector<HTMLElement>(':focus-visible, input, button')?.focus();
  }

  private async _switchAuthMethod(methodClass: string) {
    if (!this._twoFactorData) return;

    this._switchingMethod = true;
    this._clearError();

    try {
      const {data} = await actionClient.post('users/auth-form', {
        method: methodClass,
      });

      this._show2faForm(data);
    } catch {
      this._switchingMethod = false;
    }
  }

  private _onResetInput() {
    if (this._resetValidateOnInput && this._validateReset() === true) {
      this._clearError();
    }
  }

  private async _onResetSubmit(event: Event) {
    event.preventDefault();

    const error = this._validateReset();
    if (error !== true) {
      this._showError(error);
      this._resetValidateOnInput = true;
      return;
    }

    this._clearError();
    this._resetBusy = true;
    this._announce(t('Loading'));

    try {
      await actionClient.post('users/send-password-reset-email', {
        loginName: this._resetUsernameInput!.value,
      });

      this._showMessageSentModal();
    } catch (e: any) {
      this._showError(
        e?.response?.data?.message ?? t('A server error occurred.')
      );
    } finally {
      this._resetBusy = false;
    }
  }

  private _showMessageSentModal() {
    const dialog = document.createElement('craft-dialog');
    dialog.setAttribute('open', '');
    const msg = document.createElement('p');
    msg.textContent = t(
      'Check your email for instructions to reset your password.'
    );
    dialog.appendChild(msg);
    document.body.appendChild(dialog);
  }

  private _showResetPasswordForm() {
    this._clearError();
    this._view = 'reset-password';
    this.updateComplete.then(() => {
      if (this._usernameInput?.value && this._resetUsernameInput) {
        this._resetUsernameInput.value = this._usernameInput.value;
      }
      this._resetUsernameInput?.focus();
    });
  }

  private _showLoginForm() {
    this._clearError();
    const resetUsername = this._resetUsernameInput?.value ?? '';
    this._view = 'login';
    this.updateComplete.then(() => {
      if (resetUsername && this._usernameInput) {
        this._usernameInput.value = resetUsername;
      }
      this._usernameInput?.focus();
    });
  }

  private async _loginWithPasskey() {
    if (this._passkeyBusy) return;

    this._clearError();
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

      this._handleLoginSuccess(data.returnUrl);
    } catch (e: any) {
      this._passkeyBusy = false;

      const message = e?.response?.data?.message;
      if (message) {
        this._showError(message);
      } else {
        console.warn(e);
      }
    }
  }

  private _handleLoginSuccess(returnUrl: string) {
    this.dispatchEvent(
      new CustomEvent('craft-login', {
        bubbles: true,
        composed: true,
        detail: {returnUrl},
      })
    );
    window.location.href = returnUrl;
  }

  private _renderError() {
    if (!this._error) return nothing;
    return html`<p class="login-errors">${this._error}</p>`;
  }

  private _renderLoginForm() {
    const hasAltMethods =
      this._canUsePasskey || this.querySelector('[slot="alternative-methods"]');

    return html`
      <div class="login-form-container pane secondary">
        <form
          class="login-form"
          method="post"
          accept-charset="UTF-8"
          @submit="${this._onLoginSubmit}"
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
                    @input="${this._onLoginInput}"
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
              @input="${this._onLoginInput}"
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
                  <label for="login-remember-me">${this.rememberMeLabel || t('Stay signed in')}</label>
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

        ${this._renderError()}
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

  private _renderResetPasswordForm() {
    return html`
      <div class="login-form-container pane secondary">
        <form
          class="login-reset-password"
          method="post"
          accept-charset="UTF-8"
          @submit="${this._onResetSubmit}"
        >
          <div class="field">
            <label for="reset-username">${this._usernameLabel()}</label>
            <input
              id="reset-username"
              type="${this.useEmailAsUsername ? 'email' : 'text'}"
              class="reset-username"
              name="username"
              autocomplete="username"
              autocapitalize="off"
              aria-required="true"
              @input="${this._onResetInput}"
            />
          </div>

          <craft-button
            type="submit"
            variant="primary"
            ?loading="${this._resetBusy}"
          >
            ${t('Reset password')}
          </craft-button>

          ${this._renderError()}

          <hr />

          <div class="login-alt-container">
            <craft-button
              type="button"
              appearance="plain"
              @click="${this._showLoginForm}"
            >
              ${t('← Back to sign in')}
            </craft-button>
          </div>
        </form>
      </div>
    `;
  }

  private _render2faForm() {
    const data = this._twoFactorData!;

    if (this._switchingMethod) {
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
        <div class="auth-form-container">
          ${unsafeHTML(data.authForm)}
        </div>

        ${this._renderError()}

        ${data.otherMethods.length
          ? html`
              <hr />
              <div class="login-alt-container">
                <p>${t('Try another way')}</p>
                <div class="login-alt-menu">
                  ${data.otherMethods.map(
                    (method) => html`
                      <craft-button
                        type="button"
                        appearance="plain"
                        @click="${() => this._switchAuthMethod(method.class)}"
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
          ? this._renderLoginForm()
          : this._view === 'reset-password'
            ? this._renderResetPasswordForm()
            : this._render2faForm()}
      </div>
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
