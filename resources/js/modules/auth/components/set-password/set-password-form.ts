import {html, LitElement, nothing} from 'lit';
import {property, state} from 'lit/decorators.js';
import {t} from '@craftcms/ui';
import componentStyles from '../login/login-form.styles.js';

/**
 * @summary Full-page set-password form.
 * @since 6.0
 */
export default class CraftSetPasswordForm extends LitElement {
  static override styles = [componentStyles];

  @property() action = '';
  @property() uid = '';
  @property() code = '';
  @property({attribute: 'password-rules'}) passwordRules = '';
  @property({attribute: 'initial-error'}) initialError = '';
  @property({type: Boolean, attribute: 'new-user'}) newUser = false;

  @state() private _busy = false;

  #passwordLabel() {
    return this.newUser ? t('Choose a password') : t('Choose a new password');
  }

  #onSubmit() {
    this._busy = true;
  }

  override render() {
    return html`
      <craft-pane>
        <form
          class="auth-form"
          method="post"
          action="${this.action}"
          accept-charset="UTF-8"
          @submit="${this.#onSubmit}"
        >
          <input type="hidden" name="uid" value="${this.uid}" />
          <input type="hidden" name="code" value="${this.code}" />

          <craft-field-group>
            <craft-input-password
              label="${this.#passwordLabel()}"
              id="newPassword"
              name="newPassword"
              autocomplete="new-password"
              passwordrules="${this.passwordRules}"
              required
              autofocus
            ></craft-input-password>
          </craft-field-group>

          <div class="auth-form__actions">
            <craft-button
              type="submit"
              variant="accent"
              ?loading="${this._busy}"
              style="width: 100%"
            >
              ${t('Set Password')}
            </craft-button>
          </div>
        </form>

        ${this.initialError
          ? html`<craft-callout class="auth-form__error" variant="danger"
              >${this.initialError}</craft-callout
            >`
          : nothing}
      </craft-pane>
    `;
  }
}

if (!customElements.get('craft-set-password-form')) {
  customElements.define('craft-set-password-form', CraftSetPasswordForm);
}

declare global {
  interface HTMLElementTagNameMap {
    'craft-set-password-form': CraftSetPasswordForm;
  }
}
