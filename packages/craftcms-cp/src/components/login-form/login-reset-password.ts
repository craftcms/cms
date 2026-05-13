import {html, LitElement, nothing} from 'lit';
import {property, state, query} from 'lit/decorators.js';
import {actionClient} from '@src/utilities/api/actionClient.js';
import {t} from '@src/utilities/translate.js';
import componentStyles from './login-form.styles.js';
import '../button/button.js';
import '../dialog/dialog.js';

/**
 * @summary Password-reset request form. Fires `reset-back` when the user
 * wants to return to the login view.
 * @since 6.0
 *
 * @fires reset-back - User clicked "Back to sign in". Detail: `{ username: string }`
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

  private _label() {
    return this.useEmailAsUsername ? t('Email') : t('Username or Email');
  }

  private _validate(): string | true {
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

  private _onInput() {
    if (this._validateOnInput && this._validate() === true) {
      this._error = '';
    }
  }

  private async _onSubmit(event: Event) {
    event.preventDefault();

    const error = this._validate();
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
      this._error =
        e?.response?.data?.message ?? t('A server error occurred.');
    } finally {
      this._busy = false;
    }
  }

  private _onBack() {
    this.dispatchEvent(
      new CustomEvent('reset-back', {
        bubbles: true,
        composed: true,
        detail: {username: this._input?.value ?? ''},
      })
    );
  }

  override render() {
    return html`
      <div class="login-form-container pane secondary">
        <form
          class="login-reset-password"
          method="post"
          accept-charset="UTF-8"
          @submit="${this._onSubmit}"
        >
          <div class="field">
            <label for="reset-username">${this._label()}</label>
            <input
              id="reset-username"
              type="${this.useEmailAsUsername ? 'email' : 'text'}"
              class="reset-username"
              name="username"
              .value="${this.username}"
              autocomplete="username"
              autocapitalize="off"
              aria-required="true"
              @input="${this._onInput}"
            />
          </div>

          <craft-button type="submit" variant="primary" ?loading="${this._busy}">
            ${t('Reset password')}
          </craft-button>

          ${this._error
            ? html`<p class="login-errors">${this._error}</p>`
            : nothing}

          <hr />

          <div class="login-alt-container">
            <craft-button type="button" appearance="plain" @click="${this._onBack}">
              ${t('← Back to sign in')}
            </craft-button>
          </div>
        </form>
      </div>
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
