import {html, LitElement, nothing} from 'lit';
import {property, state, query} from 'lit/decorators.js';
import {actionClient} from '@src/utilities/api/actionClient.js';
import {t} from '@src/utilities/translate.js';
import componentStyles from './login-form.styles.js';
import '../button/button.js';
import '../input/input.js';

/**
 * @summary TOTP authentication code form.
 * @since 6.0
 *
 * @fires login-success - Verification succeeded. Detail: `{ returnUrl: string }`
 * @fires login-error   - Verification failed.    Detail: `{ message: string }`
 */
export default class CraftTotpForm extends LitElement {
  static override styles = [componentStyles];

  static METHOD = 'CraftCms\\Cms\\Auth\\Methods\\TOTP';

  /** Redirect destination passed through to the login-success event */
  @property({attribute: 'return-url'}) returnUrl = '';

  @state() private _busy = false;

  @query('craft-input.totp-code')
  private _input?: HTMLElement & {value: string};

  override firstUpdated() {
    this._input?.focus();
  }

  #onModelValueChanged(event: CustomEvent) {
    const value = (event.detail?.modelValue as string) ?? '';
    if (value.replace(/\s/g, '').length === 6) {
      this.#submit(value);
    }
  }

  async #onSubmit(event: Event) {
    event.preventDefault();
    this.#submit(this._input?.value ?? '');
  }

  async #submit(code: string) {
    if (this._busy) return;

    this._busy = true;

    try {
      await actionClient.post('auth/verify-totp', {code});

      this.dispatchEvent(
        new CustomEvent('login-success', {
          bubbles: true,
          composed: true,
          detail: {returnUrl: this.returnUrl},
        })
      );
    } catch (e: any) {
      this.dispatchEvent(
        new CustomEvent('login-error', {
          bubbles: true,
          composed: true,
          detail: {
            message:
              e?.response?.data?.message ?? t('A server error occurred.'),
          },
        })
      );
    } finally {
      this._busy = false;
    }
  }

  override render() {
    return html`
      <form
        class="login-form"
        accept-charset="UTF-8"
        @submit="${this.#onSubmit}"
      >
        <div class="field">
          <label for="totp-code">${t('Authentication Code')}</label>
          <craft-input
            id="totp-code"
            class="totp-code"
            name="code"
            .maxlength="${6}"
            center
            autocomplete="one-time-code"
            inputmode="numeric"
            aria-required="true"
            @model-value-changed="${this.#onModelValueChanged}"
          ></craft-input>
        </div>

        ${this._busy
          ? nothing
          : html`
              <craft-button type="submit" variant="primary">
                ${t('Verify')}
              </craft-button>
            `}
      </form>
    `;
  }
}

if (!customElements.get('craft-totp-form')) {
  customElements.define('craft-totp-form', CraftTotpForm);
}

declare global {
  interface HTMLElementTagNameMap {
    'craft-totp-form': CraftTotpForm;
  }
}
