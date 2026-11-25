import {afterEach, expect, it} from 'vite-plus/test';
import '@craftcms/ui/components/input/input';
import CraftLoginChallenge, {type TwoFactorData} from './login-challenge';

/** The innermost element holding focus, followed into shadow roots. */
function deepActiveElement(): Element | null {
  let active = document.activeElement;
  while (active?.shadowRoot?.activeElement) {
    active = active.shadowRoot.activeElement;
  }
  return active;
}

afterEach(() => {
  document.body.innerHTML = '';
});

it('focuses the verification code input on the login screen', async () => {
  const challenge = new CraftLoginChallenge();
  challenge.data = {
    authForm: '<craft-totp-form return-url="/"></craft-totp-form>',
    headHtml: '',
    bodyHtml: '',
    returnUrl: '/',
    authMethod: 'CraftCms\\Cms\\Auth\\Methods\\TOTP',
    otherMethods: [],
  } satisfies TwoFactorData;
  document.body.append(challenge);

  await challenge.updateComplete;
  await challenge.shadowRoot!.querySelector<
    HTMLElement & {updateComplete: Promise<unknown>}
  >('craft-totp-form')!.updateComplete;
  // `updated()` focuses the form once it has rendered.
  await new Promise((resolve) => setTimeout(resolve));

  expect(deepActiveElement()?.closest('craft-input')?.classList).toContain(
    'totp-code'
  );
});
