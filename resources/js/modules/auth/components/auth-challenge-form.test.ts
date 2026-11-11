import {afterEach, expect, it} from 'vite-plus/test';
import '@craftcms/ui/components/input/input';
import './totp/totp-form';
import type {CraftAuthChallengeForm} from './auth-challenge-form';

/** The elements holding focus, from the document down through shadow roots. */
function focusChain(): Element[] {
  const chain: Element[] = [];
  let active = document.activeElement;
  while (active) {
    chain.push(active);
    active = active.shadowRoot?.activeElement ?? null;
  }
  return chain;
}

async function renderForm(): Promise<CraftAuthChallengeForm> {
  document.body.insertAdjacentHTML(
    'beforeend',
    '<craft-totp-form return-url="/"></craft-totp-form>'
  );
  const form =
    document.querySelector<CraftAuthChallengeForm>('craft-totp-form')!;
  await form.updateComplete;
  return form;
}

afterEach(() => {
  document.body.innerHTML = '';
});

it('leaves focus where it is when it renders, so a setup slideout keeps it on its heading', async () => {
  document.body.innerHTML = '<h2 tabindex="-1">Authenticator App Setup</h2>';
  const heading = document.querySelector<HTMLElement>('h2')!;
  heading.focus();

  await renderForm();

  expect(document.activeElement).toBe(heading);
});

it('focuses its code input when asked to', async () => {
  const form = await renderForm();

  form.focus();

  const focused = focusChain().at(-1);
  expect(focused?.closest('craft-input')?.classList).toContain('totp-code');
});
