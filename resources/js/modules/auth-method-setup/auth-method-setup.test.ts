import {afterEach, expect, it, vi} from 'vite-plus/test';
import {actionClient} from '@craftcms/ui/utilities/api/actionClient';
import {AuthMethodSetup} from './auth-method-setup';

afterEach(() => {
  document.body.innerHTML = '';
  vi.restoreAllMocks();
});

it('replaces the method listing with the refreshed one', async () => {
  document.body.innerHTML = `
    <div id="auth-method-setup">
      <div class="auth-method" data-method="totp"><button class="auth-method-setup-btn">Set up</button></div>
    </div>`;
  vi.spyOn(actionClient, 'post').mockResolvedValue({
    data: {
      html: `<div id="auth-method-setup">
        <div class="auth-method" data-method="totp"><button class="auth-method-setup-btn">Remove</button></div>
        <div class="auth-method" data-method="webauthn"><button class="auth-method-setup-btn">Set up</button></div>
      </div>`,
      headHtml: '',
      bodyHtml: '',
    },
  });
  const onRefresh = vi.fn();
  const setup = new AuthMethodSetup({onRefresh});

  setup.refresh();
  await vi.waitFor(() => expect(onRefresh).toHaveBeenCalled());

  const container = document.getElementById('auth-method-setup')!;
  expect(
    Array.from(container.children).map((el) => el.getAttribute('data-method'))
  ).toEqual(['totp', 'webauthn']);
  expect(container.textContent).toContain('Remove');
  expect(Object.keys(setup.methodListings!)).toEqual(['totp', 'webauthn']);
});
