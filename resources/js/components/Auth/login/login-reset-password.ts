import {html, LitElement, nothing} from 'lit';
import {property, query, state} from 'lit/decorators.js';
import {actionClient, t} from '@craftcms/cp';
import componentStyles from './login-form.styles.js';

/**
 * @summary Password-reset request form. Fires `reset-back` when the user
 * wants to return to the login view.
 * @since 6.0
 *
 * @fires craft:login:reset-back - User clicked "Back to sign in". Detail: `{ username: string }`
 */
export default class CraftLoginResetPassword extends LitElement {
  static override styles = [componentStyles];

  /** Whether email is used as the username */
  @property({type: Boolean, attribute: 'use-email-as-username'})
  useEmailAsUsername = false;

  /** Pre-filled value carried over from the login form */
  @property() username = '';

  @state() private _busy = false;
  @state() private _error = '';
  @state() private _validateOnInput = false;

  @query('.reset-username') private _input?: HTMLInputElement;

  override firstUpdated() {
    this._input?.focus();
  }

  #label() {
    return this.useEmailAsUsername ? t('Email') : t('Username or Email');
  }

  #validate(): string | true {
    const value = this._input?.value ?? '';

    if (value.length === 0) {
      return this.useEmailAsUsername
        ? t('Invalid email.')
        : t('Invalid username or email.');
    }

    if (this.useEmailAsUsername && !value.match(/.+@.+\..+/)) {
      return t('Invalid email.');
    }

    return true;
  }

  #onInput() {
    if (this._validateOnInput && this.#validate() === true) {
      this._error = '';
    }
  }

  async #onSubmit(event: Event) {
    event.preventDefault();

    const error = this.#validate();
    if (error !== true) {
      this._error = error;
      this._validateOnInput = true;
      return;
    }

    this._error = '';
    this._busy = true;

    try {
      await actionClient.post('users/send-password-reset-email', {
        loginName: this._input!.value,
      });

      const dialog = document.createElement('craft-dialog');
      dialog.setAttribute('open', '');
      const msg = document.createElement('p');
      msg.textContent = t(
        'Check your email for instructions to reset your password.'
      );
      dialog.appendChild(msg);
      document.body.appendChild(dialog);
    } catch (e: any) {
      this._error = e?.response?.data?.message ?? t('A server error occurred.');
    } finally {
      this._busy = false;
    }
  }

  #onBack() {
    this.dispatchEvent(
      new CustomEvent('craft:login:reset-back', {
        bubbles: true,
        composed: true,
        detail: {username: this._input?.value ?? ''},
      })
    );
  }

  override render() {
    return html`
      <craft-pane>
        <form
          class="login-form login-form--reset"
          method="post"
          accept-charset="UTF-8"
          @submit="${this.#onSubmit}"
        >
          <craft-field-group>
            <craft-input
              label="${this.#label()}"
              id="reset-username"
              type="${this.useEmailAsUsername ? 'email' : 'text'}"
              class="reset-username"
              name="username"
              .value="${this.username}"
              autocomplete="username"
              autocapitalize="off"
              aria-required="true"
              @input="${this.#onInput}"
            >
            </craft-input>
          </craft-field-group>

          <div class="login-form__actions">
            <craft-button
              type="submit"
              variant="primary"
              ?loading="${this._busy}"
            >
              ${t('Reset password')}
            </craft-button>
          </div>

          ${this._error
            ? html`<craft-callout variant="danger" class="login-form__error"
                >${this._error}</craft-callout
              >`
            : nothing}
        </form>

        <hr />

        <craft-button
          type="button"
          appearance="plain"
          size="small"
          @click="${this.#onBack}"
        >
          <craft-icon slot="prefix" name="arrow-left"></craft-icon>
          ${t('Back to sign in')}
        </craft-button>
      </craft-pane>
    `;
  }
}

if (!customElements.get('craft-login-reset-password')) {
  customElements.define('craft-login-reset-password', CraftLoginResetPassword);
}

declare global {
  interface HTMLElementTagNameMap {
    'craft-login-reset-password': CraftLoginResetPassword;
  }
}
