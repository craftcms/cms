import {html, LitElement} from 'lit';
import {property, query, state} from 'lit/decorators.js';
import {actionClient, t} from '@src/index.js';
import componentStyles from './login-form.styles.js';

/**
 * @summary TOTP authentication code form.
 * @since 6.0
 *
 * @fires login-verified - Verification succeeded (internal). Detail: `{ returnUrl: string }`
 * @fires login-failed   - Verification failed (internal).   Detail: `{ message: string }`
 */
export default class CraftTotpForm extends LitElement {
  static override styles = [componentStyles];

  static METHOD = 'CraftCms\\Cms\\Auth\\Methods\\TOTP';

  /** Redirect destination passed through to the login-success event */
  @property({attribute: 'return-url'}) returnUrl = '';

  @state() private _state: 'idle' | 'loading' | 'success' | 'error' = 'idle';

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
    await this.#submit(this._input?.value ?? '');
  }

  async #submit(code: string) {
    if (this._state === 'loading') {
      return;
    }

    this._state = 'loading';

    try {
      await actionClient.post('auth/verify-totp', {code});

      this._state = 'success';
      this.dispatchEvent(
        new CustomEvent('login-verified', {
          bubbles: true,
          composed: true,
          detail: {returnUrl: this.returnUrl},
        })
      );

      // @TODO hook up to global feedback time setting
      setTimeout(() => {
        this._state = 'idle';
      }, 2000);
    } catch (e: any) {
      this._state = 'error';
      this.dispatchEvent(
        new CustomEvent('login-failed', {
          bubbles: true,
          composed: true,
          detail: {
            message:
              e?.response?.data?.message ?? t('A server error occurred.'),
          },
        })
      );
    }
  }

  override render() {
    return html`
      <form
        class="login-form"
        accept-charset="UTF-8"
        @submit="${this.#onSubmit}"
      >
        <div class="login-form__fields">
          <craft-input
            label="${t('Verification Code')}"
            id="totp-code"
            class="totp-code"
            name="code"
            .maxlength="${6}"
            autocomplete="one-time-code"
            inputmode="numeric"
            aria-required="true"
            @model-value-changed="${this.#onModelValueChanged}"
          >
          </craft-input>
          <craft-button
            slot="after"
            type="submit"
            variant="primary"
            ?loading="${this._state === 'loading'}"
          >
            ${t('Verify')}
          </craft-button>
        </div>
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
