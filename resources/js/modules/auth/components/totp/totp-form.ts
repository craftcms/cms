import {html} from 'lit';
import {query} from 'lit/decorators.js';
import {t} from '@craftcms/ui';
import {CraftAuthChallengeForm} from '../auth-challenge-form';

/**
 * @summary TOTP authentication code form.
 * @since 6.0
 *
 * @fires login-verified - Verification succeeded; bubbles to `craft-login-challenge`. Detail: `{ returnUrl: string }`
 * @fires login-failed   - Verification failed; bubbles to `craft-login-challenge`.   Detail: `{ message: string }`
 */
export default class CraftTotpForm extends CraftAuthChallengeForm {
  static override METHOD = 'totp';

  @query('craft-input.totp-code')
  protected override _input?: HTMLElement & {value: string};

  protected override get endpoint() {
    return 'auth/verify-totp';
  }

  override renderInput() {
    return html`
      <craft-input
        label="${t('Verification Code')}"
        id="totp-code"
        class="totp-code"
        name="code"
        .maxlength="${6}"
        autocomplete="one-time-code"
        inputmode="numeric"
        aria-required="true"
      ></craft-input>
    `;
  }
}

CraftAuthChallengeForm.register('craft-totp-form', CraftTotpForm);

declare global {
  interface HTMLElementTagNameMap {
    'craft-totp-form': CraftTotpForm;
  }
}
