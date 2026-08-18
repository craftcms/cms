import {html, LitElement, nothing} from 'lit';
import {property, query, state} from 'lit/decorators.js';
import {
  browserSupportsWebAuthn,
  platformAuthenticatorIsAvailable,
  startAuthentication,
} from '@simplewebauthn/browser';
import {
  actionClient,
  t,
  visuallyHiddenStyles,
  ConfigService,
} from '@craftcms/ui';
import componentStyles from './login-form.styles.js';
import type {TwoFactorData} from './login-challenge.js';
import './login-challenge.js';
import './login-reset-password.js';
import {useAnnouncer} from '@/common/composables/useAnnouncer';
type View = 'login' | 'reset-password' | 'challenge';

/**
 * @summary Full-page login form with password reset and passkey support.
 * @since 6.0
 *
 * @slot alternative-methods - Additional sign-in buttons (OAuth, SSO, etc.)
 *
 * @fires craft:login:success - When login succeeds. Cancelable — call `preventDefault()` to suppress navigation. Detail: `{ returnUrl: string }`
 * @fires craft:login:error   - When login fails.    Cancelable — call `preventDefault()` to suppress built-in error display. Detail: `{ message: string }`
 */
export default class CraftLoginForm extends LitElement {
  static override styles = [visuallyHiddenStyles, componentStyles];

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

  /** "Remember me" label with duration baked in by the server */
  @property({attribute: 'remember-me-label'}) rememberMeLabel = '';

  /** Error message pre-rendered by the server */
  @property({attribute: 'initial-error'}) initialError = '';

  /** Action to submit the form */
  @property() action = '';

  @state() private _view: View = 'login';
  @state() private _error = '';
  @state() private _loginBusy = false;
  @state() private _passkeyBusy = false;
  @state() private _canUsePasskey = false;
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

  override focus(options?: FocusOptions): void {
    void this.updateComplete.then(() => {
      const input = this.staticEmail
        ? this._passwordInput
        : (this._usernameInput ?? this._passwordInput);

      input?.focus(options);
    });
  }

  #usernameLabel() {
    return this.useEmailAsUsername ? t('Email') : t('Username or Email');
  }

  async #onSubmit(event: Event) {
    event.preventDefault();

    this._error = '';
    this._loginBusy = true;

    try {
      const response = await fetch(this.action, {
        method: 'post',
        headers: {
          Accept: 'application/json',
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': ConfigService.getInstance().get('csrfTokenValue'),
        },
        body: JSON.stringify({
          loginName: this._usernameInput!.value,
          password: this._passwordInput!.value,
          rememberMe: this._rememberMeInput?.checked ? '1' : '',
        }),
      });

      const data = (await response.json()) as TwoFactorData & {
        message?: string;
      };

      if (!response.ok) {
        throw new Error(data.message || 'A server error occurred.');
      }

      if (data.authMethod) {
        this._twoFactorData = data;
        this._view = 'challenge';
        this._loginBusy = false;
      } else {
        this.#handleSuccess(data.returnUrl);
        this._loginBusy = false;
      }
    } catch (e: any) {
      this._loginBusy = false;
      this.#setError(e.message);
    }
  }

  async #loginWithPasskey() {
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
        authResponse: JSON.stringify(authResponse),
      });

      this.#handleSuccess(data.returnUrl);
      this._passkeyBusy = false;
    } catch (e: any) {
      this._passkeyBusy = false;
      const message = e?.response?.data?.message;
      if (message) {
        this.#setError(message);
      } else {
        console.warn(e);
      }
    }
  }

  #showResetPasswordForm() {
    this._error = '';
    this._resetUsername = this._usernameInput?.value ?? '';
    this._view = 'reset-password';
  }

  #onResetBack(event: CustomEvent) {
    const username = (event.detail?.username as string) ?? '';
    this._view = 'login';
    this.updateComplete.then(() => {
      if (username && this._usernameInput) this._usernameInput.value = username;
      this._usernameInput?.focus();
    });
  }

  #onLoginSuccess(event: CustomEvent) {
    this.#handleSuccess((event.detail as {returnUrl: string}).returnUrl);
  }

  #onLoginError(event: CustomEvent) {
    const message = (event.detail as {message: string}).message;
    const errorEvent = new CustomEvent('craft:login:error', {
      bubbles: true,
      composed: true,
      cancelable: true,
      detail: {message},
    });
    this.dispatchEvent(errorEvent);
    if (!errorEvent.defaultPrevented) {
      this.#setError(message);
    }
  }

  #setError(message: string) {
    const {announce} = useAnnouncer();
    this._error = message.trim();
    announce(this._error);
  }

  #handleSuccess(returnUrl: string) {
    const event = new CustomEvent('craft:login:success', {
      bubbles: true,
      composed: true,
      cancelable: true,
      detail: {returnUrl},
    });
    this.dispatchEvent(event);
    if (!event.defaultPrevented) {
      window.location.href = returnUrl;
    }
  }

  override render() {
    return html`
      <div>
        <span
          class="cp-visually-hidden"
          role="status"
          aria-live="polite"
          aria-atomic="true"
        ></span>

        ${this._view === 'login'
          ? this.#renderLoginView()
          : this._view === 'reset-password'
            ? html`
                <craft-login-reset-password
                  ?use-email-as-username="${this.useEmailAsUsername}"
                  username="${this._resetUsername}"
                  @craft:login:reset-back="${this.#onResetBack}"
                ></craft-login-reset-password>
              `
            : html`
                <craft-login-challenge
                  .data="${this._twoFactorData!}"
                  @login-verified="${this.#onLoginSuccess}"
                  @login-failed="${this.#onLoginError}"
                ></craft-login-challenge>
              `}
      </div>
    `;
  }

  #renderLoginView() {
    const hasAltMethods =
      this._canUsePasskey || this.querySelector('[slot="alternative-methods"]');

    return html`
      <craft-pane>
        <form
          class="auth-form"
          method="post"
          accept-charset="UTF-8"
          @submit="${this.#onSubmit}"
        >
          <craft-field-group>
            ${this.staticEmail
              ? html`<input
                  type="hidden"
                  class="login-username"
                  name="username"
                  .value="${this.staticEmail}"
                />`
              : html`
                  <div class="field">
                    <craft-input
                      label="${this.#usernameLabel()}"
                      id="login-username"
                      type="${this.useEmailAsUsername ? 'email' : 'text'}"
                      class="login-username"
                      name="username"
                      .value="${this.username}"
                      autocomplete="username"
                      autocapitalize="off"
                      required
                    />
                  </div>
                `}

            <div>
              <craft-input-password
                label="${t('Password')}"
                id="login-password"
                class="login-password"
                name="password"
                autocomplete="current-password"
                required
              ></craft-input-password>

              ${this.showResetPassword
                ? html`
                    <craft-button
                      type="button"
                      size="small"
                      variant="link"
                      @click="${this.#showResetPasswordForm}"
                      style="margin-block-start: var(--c-spacing-sm)"
                    >
                      ${t('Forgot password?')}
                    </craft-button>
                  `
                : nothing}
            </div>

            ${this.showRememberMe
              ? html`
                  <div class="remember-me-row">
                    <craft-checkbox
                      label="${this.rememberMeLabel || t('Stay signed in')}"
                      type="checkbox"
                      id="login-remember-me"
                      class="login-remember-me"
                    ></craft-checkbox>
                  </div>
                `
              : nothing}
          </craft-field-group>

          <div class="auth-form__actions">
            <craft-button
              type="submit"
              variant="primary"
              ?loading="${this._loginBusy}"
              style="width: 100%"
            >
              ${t('Sign in')}
            </craft-button>
          </div>
        </form>

        ${this._error
          ? html`<craft-callout class="auth-form__error" variant="danger"
              >${this._error}</craft-callout
            >`
          : nothing}
      </craft-pane>

      ${hasAltMethods
        ? html`
            <div class="alternative-login-methods">
              ${this._canUsePasskey
                ? html`
                    <craft-button
                      type="button"
                      variant="primary"
                      ?loading="${this._passkeyBusy}"
                      @click="${this.#loginWithPasskey}"
                      style="width: 100%"
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
