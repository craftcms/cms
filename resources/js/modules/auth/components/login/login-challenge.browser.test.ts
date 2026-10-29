import {afterEach, expect, it, vi} from 'vite-plus/test';
import type {TwoFactorData} from './login-challenge';
import './login-challenge';

afterEach(() => {
  document.body.replaceChildren();
  vi.restoreAllMocks();
  vi.unstubAllGlobals();
});

it.each([
  [
    'an error response',
    () =>
      Promise.resolve({
        ok: false,
        json: async () => ({message: 'Your session has expired.'}),
      }),
    'Your session has expired.',
  ],
  [
    'a network failure',
    () => Promise.reject(new TypeError('Failed to fetch')),
    'A server error occurred.',
  ],
])(
  'reports a failed method switch after %s and restores the form once',
  async (_case, fetchResponse, expectedMessage) => {
    const Craft = {
      appendHeadHtml: vi.fn().mockResolvedValue(undefined),
      appendBodyHtml: vi.fn().mockResolvedValue(undefined),
      initUiElements: vi.fn(),
      createAuthFormHandler: vi.fn(),
    };
    vi.stubGlobal('Craft', Craft);
    vi.stubGlobal('fetch', vi.fn(fetchResponse));

    const data: TwoFactorData = {
      authForm: '<form class="sms-form"><input name="code"></form>',
      headHtml: '<style>.sms-form {}</style>',
      bodyHtml: '<script>window.smsReady = true;</script>',
      returnUrl: '/admin/dashboard',
      authMethod: 'plugin\\auth\\Sms',
      otherMethods: [
        {name: 'Recovery code', handle: 'recovery', url: '/challenge/recovery'},
      ],
    };
    const challenge = document.createElement('craft-login-challenge');
    challenge.data = data;
    const failures: string[] = [];
    challenge.addEventListener('login-failed', (event) =>
      failures.push((event as CustomEvent<{message: string}>).detail.message)
    );
    document.body.append(challenge);
    await vi.waitFor(() =>
      expect(challenge.shadowRoot!.querySelector('.sms-form')).not.toBeNull()
    );

    challenge
      .shadowRoot!.querySelector<HTMLElement>('craft-action-item')!
      .click();

    await vi.waitFor(() => expect(failures).toEqual([expectedMessage]));
    await vi.waitFor(() =>
      expect(challenge.shadowRoot!.querySelector('.sms-form')).not.toBeNull()
    );
    expect(Craft.appendHeadHtml).toHaveBeenCalledTimes(1);
    expect(Craft.appendBodyHtml).toHaveBeenCalledTimes(1);
  }
);
