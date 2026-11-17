import {afterEach, expect, it, vi} from 'vite-plus/test';
import type {TwoFactorData} from './login-challenge';
import './login-challenge';

const smsStyles = () =>
  [...document.head.querySelectorAll('style')].filter((style) =>
    style.textContent?.includes('.sms-form')
  );

afterEach(() => {
  smsStyles().forEach((style) => style.remove());
  delete (window as {smsReady?: boolean}).smsReady;
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
    expect(smsStyles()).toHaveLength(1);
    expect((window as {smsReady?: boolean}).smsReady).toBe(true);
  }
);

it('reports a legacy auth method it can’t wire up, rather than hanging', async () => {
  // No `createAuthFormHandler`: the Yii2 adapter isn't on the page.
  vi.stubGlobal('Craft', {initUiElements: vi.fn()});

  const data: TwoFactorData = {
    authForm: '<form class="sms-form"><input name="code"></form>',
    headHtml: '',
    bodyHtml: '',
    returnUrl: '/admin/dashboard',
    authMethod: 'plugin\\auth\\Sms',
    otherMethods: [],
  };
  const challenge = document.createElement('craft-login-challenge');
  challenge.data = data;
  const failures: string[] = [];
  challenge.addEventListener('login-failed', (event) =>
    failures.push((event as CustomEvent<{message: string}>).detail.message)
  );
  document.body.append(challenge);

  await vi.waitFor(() => expect(failures).toHaveLength(1));
  expect(failures[0]).toContain('isn’t available here');
});

it.each([
  ['CraftCms\\Cms\\Auth\\Methods\\TOTP', 'craft-totp-form'],
  ['CraftCms\\Cms\\Auth\\Methods\\RecoveryCodes', 'craft-recovery-code-form'],
])(
  'renders the native form for %s without a legacy handler',
  async (authMethod, tagName) => {
    const Craft = {
      appendHeadHtml: vi.fn().mockResolvedValue(undefined),
      appendBodyHtml: vi.fn().mockResolvedValue(undefined),
      initUiElements: vi.fn(),
      createAuthFormHandler: vi.fn(),
    };
    vi.stubGlobal('Craft', Craft);

    const challenge = document.createElement('craft-login-challenge');
    challenge.data = {
      authForm: `<${tagName} return-url="/admin/dashboard"></${tagName}>`,
      headHtml: '',
      bodyHtml: '',
      returnUrl: '/admin/dashboard',
      authMethod,
      otherMethods: [],
    };
    document.body.append(challenge);

    await vi.waitFor(() =>
      expect(
        challenge.shadowRoot!.querySelector(tagName)?.shadowRoot
      ).toBeTruthy()
    );
    expect(Craft.createAuthFormHandler).not.toHaveBeenCalled();
  }
);
