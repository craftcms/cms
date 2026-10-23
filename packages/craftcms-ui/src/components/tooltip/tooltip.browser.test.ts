import {beforeEach, expect, it} from 'vite-plus/test';
import type CraftTooltip from './tooltip.js';
import './tooltip.js';

beforeEach(() => {
  document.body.innerHTML = '';
});

async function render(): Promise<{
  invoker: HTMLButtonElement;
  elsewhere: HTMLElement;
  tooltip: CraftTooltip;
}> {
  document.body.innerHTML = `
    <button id="invoker" type="button">Activity</button>
    <craft-tooltip for="invoker" placement="left">Activity</craft-tooltip>
    <p style="margin-top: 200px">Elsewhere</p>`;
  const tooltip = document.querySelector('craft-tooltip')!;
  await tooltip.updateComplete;

  return {
    invoker: document.querySelector('button')!,
    elsewhere: document.querySelector('p')!,
    tooltip,
  };
}

const after = (ms: number) => new Promise((resolve) => setTimeout(resolve, ms));

it('hides once the pointer leaves an invoker it was clicked on', async () => {
  const {userEvent} = await import('vite-plus/test/browser');
  const {invoker, elsewhere, tooltip} = await render();

  await userEvent.click(invoker);
  await after(300);

  expect(tooltip.opened).toBe(true);

  await userEvent.hover(elsewhere);
  await after(100);

  expect(document.activeElement).toBe(invoker);
  expect(tooltip.opened).toBe(false);
});

it('stays open while the invoker has keyboard focus', async () => {
  const {userEvent} = await import('vite-plus/test/browser');
  const {elsewhere, tooltip} = await render();

  await userEvent.hover(elsewhere);
  await userEvent.tab();
  await after(300);

  expect(tooltip.opened).toBe(true);

  await userEvent.hover(elsewhere);
  await after(100);

  expect(tooltip.opened).toBe(true);
});

it('wraps long content at a readable width', async () => {
  document.body.innerHTML = `
    <button id="invoker" type="button">Info</button>
    <craft-tooltip for="invoker">${'Words that go on and on. '.repeat(20)}</craft-tooltip>
    <craft-tooltip for="invoker">Short</craft-tooltip>`;
  const [long, short] = document.querySelectorAll('craft-tooltip');
  await long!.updateComplete;
  await short!.updateComplete;
  long!.opened = true;
  short!.opened = true;
  await after(100);

  const longContent = long!.querySelector<HTMLElement>('[slot="content"]')!;
  const shortContent = short!.querySelector<HTMLElement>('[slot="content"]')!;
  const ch = parseFloat(getComputedStyle(longContent).fontSize) * 0.6;

  expect(longContent.getBoundingClientRect().width).toBeLessThan(ch * 60);
  expect(longContent.getBoundingClientRect().height).toBeGreaterThan(
    parseFloat(getComputedStyle(longContent).lineHeight) * 2
  );
  expect(shortContent.getBoundingClientRect().width).toBeLessThan(80);
});
