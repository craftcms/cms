import {beforeEach, expect, it} from 'vite-plus/test';
import {userEvent} from 'vite-plus/test/browser';
import type CraftButton from '../button/button.js';
import type CraftPopover from './popover.js';
import '../button/button.js';
import './popover.js';

beforeEach(() => {
  document.body.innerHTML = '';
});

it('returns focus to the invoker, and stays closed, when Enter inside it calls hide()', async () => {
  document.body.innerHTML = `
    <craft-popover>
      <craft-button slot="invoker" type="button">Open</craft-button>
      <div slot="content"><button type="button" class="pick">Pick</button></div>
    </craft-popover>
  `;
  const popover = document.querySelector<CraftPopover>('craft-popover')!;
  const invoker = popover.querySelector<CraftButton>('craft-button')!;
  const pick = popover.querySelector<HTMLButtonElement>('.pick')!;
  pick.addEventListener('click', () => {
    void popover.hide();
  });
  await invoker.updateComplete;
  invoker.focus();
  await userEvent.keyboard('{Enter}');
  await popover.updateComplete;

  pick.focus();
  await userEvent.keyboard('{Enter}');
  await popover.updateComplete;

  expect(popover.opened).toBe(false);
  expect(document.activeElement).toBe(invoker);
});
