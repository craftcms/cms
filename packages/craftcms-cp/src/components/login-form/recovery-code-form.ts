import {html, LitElement, nothing} from 'lit';
import {property, query, state} from 'lit/decorators.js';
import {actionClient, t} from '@src/index.js';
import componentStyles from './login-form.styles.js';

/**
 * @summary Recovery code authentication form.
 * @since 6.0
 *
 * @fires login-success - Verification succeeded. Detail: `{ returnUrl: string }`
 * @fires login-error   - Verification failed.    Detail: `{ message: string }`
 */
export default class CraftRecoveryCodeForm extends LitElement {
  static override styles = [componentStyles];

  static METHOD = 'CraftCms\\Cms\\Auth\\Methods\\RecoveryCodes';

  /** Redirect destination passed through to the login-success event */
  @property({attribute: 'return-url'}) returnUrl = '';

  @state() private _busy = false;

  @query('craft-input.recovery-code')
  private _input?: HTMLElement & {value: string};

  override firstUpdated() {
    this._input?.focus();
  }

  #onModelValueChanged(event: CustomEvent) {
    const value = (event.detail?.modelValue as string) ?? '';
    if (value.replace(/-/g, '').length === 12) {
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
      await actionClient.post('auth/verify-recovery-code', {code});

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
          <label for="recovery-code">${t('Recovery Code')}</label>
          <craft-input
            id="recovery-code"
            class="recovery-code"
            name="code"
            autocomplete="off"
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

if (!customElements.get('craft-recovery-code-form')) {
  customElements.define('craft-recovery-code-form', CraftRecoveryCodeForm);
}

declare global {
  interface HTMLElementTagNameMap {
    'craft-recovery-code-form': CraftRecoveryCodeForm;
  }
}
