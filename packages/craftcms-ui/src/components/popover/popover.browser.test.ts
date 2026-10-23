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

it('returns focus to the invoker when Escape closes it from its slotted content', async () => {
  document.body.innerHTML = `
    <craft-popover>
      <craft-button slot="invoker" type="button">Open</craft-button>
      <div slot="content-body"><button type="button" class="pick">Pick</button></div>
    </craft-popover>
  `;
  const popover = document.querySelector<CraftPopover>('craft-popover')!;
  const invoker = popover.querySelector<CraftButton>('craft-button')!;
  await invoker.updateComplete;
  popover.opened = true;
  await popover.updateComplete;

  popover.querySelector<HTMLButtonElement>('.pick')!.focus();
  await userEvent.keyboard('{Escape}');
  await popover.updateComplete;

  expect(popover.opened).toBe(false);
  expect(document.activeElement).toBe(invoker);
});

it('closes and keeps focus on the invoker when Escape is pressed there', async () => {
  document.body.innerHTML = `
    <craft-popover>
      <craft-button slot="invoker" type="button">Open</craft-button>
      <div slot="content-body"><button type="button">Pick</button></div>
    </craft-popover>
  `;
  const popover = document.querySelector<CraftPopover>('craft-popover')!;
  const invoker = popover.querySelector<CraftButton>('craft-button')!;
  await invoker.updateComplete;
  invoker.focus();
  await userEvent.keyboard('{Enter}');
  await popover.updateComplete;

  await userEvent.keyboard('{Escape}');
  await popover.updateComplete;

  expect(popover.opened).toBe(false);
  expect(document.activeElement).toBe(invoker);
});

it('lets one Escape close the popover around it after it was closed another way', async () => {
  document.body.innerHTML = `
    <craft-popover class="outer">
      <craft-button slot="invoker" type="button">Outer</craft-button>
      <div slot="content-body">
        <craft-popover class="inner">
          <craft-button slot="invoker" type="button">Inner</craft-button>
          <div slot="content-body"><button type="button">Pick</button></div>
        </craft-popover>
      </div>
    </craft-popover>
  `;
  const outer = document.querySelector<CraftPopover>('.outer')!;
  const inner = document.querySelector<CraftPopover>('.inner')!;
  const outerInvoker = outer.querySelector<CraftButton>('craft-button')!;
  const innerInvoker = inner.querySelector<CraftButton>('craft-button')!;
  await outerInvoker.updateComplete;
  outerInvoker.focus();
  await userEvent.keyboard('{Enter}');
  await outer.updateComplete;
  await inner.show();
  await inner.hide();

  innerInvoker.focus();
  await userEvent.keyboard('{Escape}');
  await outer.updateComplete;

  expect(outer.opened).toBe(false);
  expect(document.activeElement).toBe(outerInvoker);
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
