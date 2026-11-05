import {afterEach, expect, it, vi} from 'vite-plus/test';
import './recovery-codes-setup';

afterEach(() => {
  document.body.innerHTML = '';
  vi.restoreAllMocks();
  vi.unstubAllGlobals();
});

function setup(response: {ok: boolean; status: number; body: unknown}) {
  const cp = {announce: vi.fn(), displayError: vi.fn()};
  vi.stubGlobal('Craft', {
    cp,
    t: (_category: string, message: string) => message,
  });
  vi.stubGlobal(
    'fetch',
    vi.fn().mockResolvedValue({
      ok: response.ok,
      status: response.status,
      json: async () => response.body,
    })
  );

  const template = document.createElement('template');
  template.innerHTML = `<craft-recovery-codes-setup container-id="setup">
    <form action="/actions/auth/generate-recovery-codes" method="post">
      <button type="submit">Generate recovery codes</button>
    </form>
  </craft-recovery-codes-setup>`;
  document.body.append(template.content.cloneNode(true));

  const host = document.querySelector('craft-recovery-codes-setup')!;
  const button = host.querySelector('button')!;
  button.focus();
  host.querySelector('form')!.requestSubmit(button);

  return {cp, host};
}

it('keeps the form and shows the error when the elevated session has expired', async () => {
  const message = 'This action may only be performed with an elevated session.';
  const {cp, host} = setup({ok: false, status: 423, body: {message}});

  await vi.waitFor(() => expect(cp.displayError).toHaveBeenCalledWith(message));
  expect(host.querySelector('form')).not.toBeNull();
  expect(host.querySelector('.auth-method-setup-success-message')).toBeNull();
});

it('moves focus to the success heading after generating codes', async () => {
  const {cp, host} = setup({
    ok: true,
    status: 200,
    body: {message: 'Recovery codes generated.', codes: ['abc-123', 'def-456']},
  });

  await vi.waitFor(() =>
    expect(document.activeElement).toBe(
      host.querySelector('.auth-method-setup-success-message')
    )
  );
  expect(host.querySelector('form')).toBeNull();
  expect([...host.querySelectorAll('li')].map((li) => li.textContent)).toEqual([
    'abc-123',
    'def-456',
  ]);
  expect(cp.displayError).not.toHaveBeenCalled();
});
