import {beforeEach, expect, it} from 'vite-plus/test';
import {userEvent} from 'vite-plus/test/browser';
import '../button/button.js';
import type CraftPopover from './popover.js';
import './popover.js';

beforeEach(() => {
  document.body.innerHTML = '';
});

it('keeps Escape from reaching a container while it closes the popover', async () => {
  document.body.innerHTML = `
    <section>
      <craft-popover label="Context">
        <craft-button slot="invoker" type="button">Context</craft-button>
        <div slot="content"><label><input type="checkbox"> Title</label></div>
      </craft-popover>
    </section>`;
  const popover = document.querySelector<CraftPopover>('craft-popover')!;
  const escapes: boolean[] = [];
  document
    .querySelector('section')!
    .addEventListener('keydown', (event) =>
      escapes.push(event.defaultPrevented)
    );
  await popover.updateComplete;

  await userEvent.click(document.querySelector('craft-button')!);
  await expect.poll(() => popover.opened).toBe(true);
  popover.querySelector('input')!.focus();
  await userEvent.keyboard('{Escape}');

  await expect.poll(() => popover.opened).toBe(false);

  // Closed, the popover leaves Escape to the container.
  document.querySelector<HTMLElement>('craft-button')!.focus();
  await userEvent.keyboard('{Escape}');

  expect(escapes).toEqual([true, false]);
});
