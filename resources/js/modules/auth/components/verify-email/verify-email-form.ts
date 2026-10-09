import {html, LitElement, nothing} from 'lit';
import {property, state} from 'lit/decorators.js';
import {t} from '@craftcms/ui';
import componentStyles from '../login/login-form.styles.js';

/**
 * @summary Full-page verify-email form.
 * @since 6.0
 */
export default class CraftVerifyEmailForm extends LitElement {
  static override styles = [componentStyles];

  @property() action = '';
  @property() uid = '';
  @property() code = '';
  @property({attribute: 'initial-error'}) initialError = '';

  @state() private _busy = false;

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

          <h2 class="auth-form__heading">${t('Verify your email address')}</h2>

          <div class="auth-form__actions">
            <craft-button
              type="submit"
              variant="accent"
              ?loading="${this._busy}"
              style="width: 100%"
            >
              ${t('Verify')}
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

if (!customElements.get('craft-verify-email-form')) {
  customElements.define('craft-verify-email-form', CraftVerifyEmailForm);
}

declare global {
  interface HTMLElementTagNameMap {
    'craft-verify-email-form': CraftVerifyEmailForm;
  }
}
