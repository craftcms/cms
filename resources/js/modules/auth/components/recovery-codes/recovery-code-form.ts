import {html} from 'lit';
import {query} from 'lit/decorators.js';
import {t} from '@craftcms/ui';
import {CraftAuthChallengeForm} from '../auth-challenge-form';

/**
 * @summary Recovery code authentication form.
 * @since 6.0
 *
 * @fires login-verified - Verification succeeded; bubbles to `craft-login-challenge`. Detail: `{ returnUrl: string }`
 * @fires login-failed   - Verification failed; bubbles to `craft-login-challenge`.   Detail: `{ message: string }`
 */
export default class CraftRecoveryCodeForm extends CraftAuthChallengeForm {
  static override METHOD = 'recovery-codes';

  @query('craft-input.recovery-code')
  protected override _input?: HTMLElement & {value: string};

  protected override get endpoint() {
    return 'auth/verify-recovery-code';
  }

  override renderInput() {
    return html`
      <craft-input
        label="${t('Recovery Code')}"
        id="recovery-code"
        class="recovery-code"
        name="code"
        autocomplete="off"
        aria-required="true"
      ></craft-input>
    `;
  }
}

CraftAuthChallengeForm.register(
  'craft-recovery-code-form',
  CraftRecoveryCodeForm
);

declare global {
  interface HTMLElementTagNameMap {
    'craft-recovery-code-form': CraftRecoveryCodeForm;
  }
}
