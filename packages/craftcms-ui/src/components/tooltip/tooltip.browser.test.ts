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

it('shows in full beside an invoker in a blurred backdrop that clips', async () => {
  document.body.innerHTML = `
    <div style="overflow: clip; width: 80px; margin: 100px 0 0 200px">
      <div style="backdrop-filter: blur(4px)">
        <button id="invoker" type="button">Publish</button>
        <craft-tooltip for="invoker" placement="top">Several drafts are blocking publication.</craft-tooltip>
      </div>
    </div>`;
  const tooltip = document.querySelector('craft-tooltip')!;
  await tooltip.updateComplete;
  tooltip.opened = true;
  await after(100);

  const invoker = document.querySelector('button')!.getBoundingClientRect();
  const content = tooltip.querySelector<HTMLElement>('[slot="content"]')!;
  const rect = content.getBoundingClientRect();

  expect(invoker.top - rect.bottom).toBeGreaterThanOrEqual(0);
  expect(invoker.top - rect.bottom).toBeLessThan(20);
  expect(rect.right).toBeGreaterThan(280);
  expect(
    document.elementFromPoint(rect.right - 4, rect.top + rect.height / 2)
  ).toBe(content);
});

async function renderWithTwoInvokers(trigger: string): Promise<{
  first: HTMLButtonElement;
  second: HTMLButtonElement;
  tooltip: CraftTooltip;
}> {
  document.body.innerHTML = `
    <button id="first" type="button">First</button>
    <button id="second" type="button">Second</button>
    <craft-tooltip for="first" trigger="${trigger}">Help</craft-tooltip>`;
  const tooltip = document.querySelector('craft-tooltip')!;
  await tooltip.updateComplete;
  await after(0);

  return {
    first: document.querySelector<HTMLButtonElement>('#first')!,
    second: document.querySelector<HTMLButtonElement>('#second')!,
    tooltip,
  };
}

it('moves its wiring and click toggle to the new invoker when for changes', async () => {
  const {first, second, tooltip} = await renderWithTwoInvokers('click');
  expect(first.hasAttribute('aria-describedby')).toBe(true);

  tooltip.for = 'second';
  await tooltip.updateComplete;

  expect(first.hasAttribute('aria-describedby')).toBe(false);
  expect(second.getAttribute('aria-describedby')).toBe(
    tooltip.querySelector('[slot="content"]')!.id
  );

  second.click();
  await after(50);
  expect(tooltip.opened).toBe(true);

  first.click();
  await after(50);
  expect(tooltip.opened).toBe(true);

  second.click();
  await tooltip.updateComplete;
  expect(tooltip.opened).toBe(false);
});

it('opens on click rather than hover once its trigger changes to click', async () => {
  const {userEvent} = await import('vite-plus/test/browser');
  const {first, tooltip} = await renderWithTwoInvokers('hover focus');

  tooltip.trigger = 'click';
  await tooltip.updateComplete;

  await userEvent.hover(first);
  await after(300);
  expect(tooltip.opened).toBe(false);

  first.click();
  await tooltip.updateComplete;
  expect(tooltip.opened).toBe(true);
});
