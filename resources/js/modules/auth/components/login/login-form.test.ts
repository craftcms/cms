import axios from 'axios';
import {afterEach, expect, it, vi} from 'vite-plus/test';
import {actionClient, ConfigService} from '@craftcms/ui';
import CraftLoginForm from './login-form';

afterEach(() => {
  document.body.innerHTML = '';
  ConfigService.resetInstance();
  delete axios.defaults.headers.common['X-CSRF-TOKEN'];
  vi.restoreAllMocks();
  vi.unstubAllGlobals();
});

it.each([true, false])(
  'only resumes the pending form after a successful token refresh (success: %s)',
  async (refreshSucceeds) => {
    ConfigService.getInstance().initialize({csrfTokenValue: 'expired-token'});
    vi.stubGlobal('Craft', {
      csrfTokenName: '_token',
      csrfTokenValue: 'expired-token',
    });
    document.body.innerHTML = `<form id="settings"><input type="hidden" name="_token" value="expired-token"><input name="title" value="Unchanged"></form>
       <form id="other"><input type="hidden" name="_token" value="another-old-token"></form>`;
    const form = document.querySelector<HTMLFormElement>('#settings')!;
    const submitted = vi.fn();
    form.addEventListener('submit', (event) => {
      event.preventDefault();
      submitted(Object.fromEntries(new FormData(form)));
    });

    let finishRefresh!: (value: unknown) => void;
    let failRefresh!: (reason: Error) => void;
    const refresh = new Promise((resolve, reject) => {
      finishRefresh = resolve;
      failRefresh = reject;
    });
    vi.spyOn(actionClient, 'get')
      .mockResolvedValueOnce({
        data: {csrfTokenName: '_token', csrfTokenValue: 'pre-login-token'},
      })
      .mockReturnValueOnce(refresh);
    const fetch = vi.fn().mockResolvedValue({
      ok: true,
      json: async () => ({returnUrl: '/dashboard'}),
    });
    vi.stubGlobal('fetch', fetch);

    const login = new CraftLoginForm();
    login.showPasskeyBtn = false;
    login.staticEmail = 'admin@example.com';
    login.action = '/login';
    login.addEventListener('craft:login:success', (event) => {
      event.preventDefault();
      form.requestSubmit();
    });
    document.body.append(login);
    await login.updateComplete;
    const password = login.shadowRoot!.querySelector('craft-input-password')!;
    password.value = 'password';
    login
      .shadowRoot!.querySelector('form')!
      .dispatchEvent(new Event('submit', {cancelable: true}));

    await vi.waitFor(() =>
      expect(fetch).toHaveBeenCalledWith(
        '/login',
        expect.objectContaining({
          headers: expect.objectContaining({'X-CSRF-TOKEN': 'pre-login-token'}),
        })
      )
    );
    expect(submitted).not.toHaveBeenCalled();
    if (!refreshSucceeds) {
      failRefresh(new Error('Unable to refresh the session.'));
      await vi.waitFor(() =>
        expect(
          login.shadowRoot!.querySelector('.auth-form__error')?.textContent
        ).toContain('Unable to refresh the session.')
      );
      expect(submitted).not.toHaveBeenCalled();
      return;
    }

    finishRefresh({
      data: {csrfTokenName: '_token', csrfTokenValue: 'post-login-token'},
    });
    await vi.waitFor(() =>
      expect(submitted).toHaveBeenCalledExactlyOnceWith({
        _token: 'post-login-token',
        title: 'Unchanged',
      })
    );
    expect(
      new FormData(document.querySelector<HTMLFormElement>('#other')!).get(
        '_token'
      )
    ).toBe('post-login-token');
    expect(Craft.csrfTokenValue).toBe('post-login-token');
    expect(ConfigService.getInstance().get('csrfTokenValue')).toBe(
      'post-login-token'
    );
    expect(axios.defaults.headers.common['X-CSRF-TOKEN']).toBe(
      'post-login-token'
    );
  }
);
