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

  expect(tooltip.open).toBe(true);

  await userEvent.hover(elsewhere);
  await after(100);

  expect(document.activeElement).toBe(invoker);
  expect(tooltip.open).toBe(false);
});

it('stays open while the invoker has keyboard focus', async () => {
  const {userEvent} = await import('vite-plus/test/browser');
  const {elsewhere, tooltip} = await render();

  await userEvent.hover(elsewhere);
  await userEvent.tab();
  await after(300);

  expect(tooltip.open).toBe(true);

  await userEvent.hover(elsewhere);
  await after(100);

  expect(tooltip.open).toBe(true);
});

it('wraps long content at a readable width', async () => {
  document.body.innerHTML = `
    <button id="invoker" type="button">Info</button>
    <craft-tooltip for="invoker">${'Words that go on and on. '.repeat(20)}</craft-tooltip>
    <craft-tooltip for="invoker">Short</craft-tooltip>`;
  const [long, short] = document.querySelectorAll('craft-tooltip');
  await long!.updateComplete;
  await short!.updateComplete;
  long!.open = true;
  short!.open = true;
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
  tooltip.open = true;
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
  expect(tooltip.open).toBe(true);

  first.click();
  await after(50);
  expect(tooltip.open).toBe(true);

  second.click();
  await tooltip.updateComplete;
  expect(tooltip.open).toBe(false);
});

it('opens on click rather than hover once its trigger changes to click', async () => {
  const {userEvent} = await import('vite-plus/test/browser');
  const {first, tooltip} = await renderWithTwoInvokers('hover focus');

  tooltip.trigger = 'click';
  await tooltip.updateComplete;

  await userEvent.hover(first);
  await after(300);
  expect(tooltip.open).toBe(false);

  first.click();
  await tooltip.updateComplete;
  expect(tooltip.open).toBe(true);
});

it('binds an invoker slotted in after it renders, in place of the old one', async () => {
  document.body.innerHTML = `
    <craft-tooltip trigger="click">
      <button slot="invoker" type="button">Old</button>
      Help
    </craft-tooltip>`;
  const tooltip = document.querySelector('craft-tooltip')!;
  await tooltip.updateComplete;
  await after(0);
  const old = tooltip.querySelector('button')!;
  const replacement = document.createElement('button');
  replacement.type = 'button';
  replacement.slot = 'invoker';
  replacement.textContent = 'New';

  old.replaceWith(replacement);
  await after(50);

  expect(old.hasAttribute('aria-describedby')).toBe(false);
  expect(replacement.getAttribute('aria-describedby')).toBe(
    tooltip.querySelector('[slot="content"]')!.id
  );

  replacement.click();
  await tooltip.updateComplete;
  expect(tooltip.open).toBe(true);
});

it('moves beside the new invoker when for changes while open', async () => {
  document.body.innerHTML = `
    <button id="first" type="button">First</button>
    <button id="second" type="button" style="margin: 200px 0 0 100px">Second</button>
    <craft-tooltip for="first" placement="right">Help</craft-tooltip>`;
  const tooltip = document.querySelector('craft-tooltip')!;
  await tooltip.show();
  await after(50);

  tooltip.for = 'second';
  await tooltip.updateComplete;
  await after(50);

  const second = document.querySelector('#second')!.getBoundingClientRect();
  const content = tooltip
    .querySelector<HTMLElement>('[slot="content"]')!
    .getBoundingClientRect();

  expect(content.left - second.right).toBeGreaterThanOrEqual(0);
  expect(content.left - second.right).toBeLessThan(20);
  expect(content.top).toBeLessThan(second.bottom);
  expect(content.bottom).toBeGreaterThan(second.top);
});

it('closes and lets go of its invoker when removed before show() settles', async () => {
  const {invoker, tooltip} = await render();

  const shown = tooltip.show();
  tooltip.remove();

  await expect(shown).resolves.toBeUndefined();
  await after(0);
  expect(tooltip.open).toBe(false);
  expect(invoker.hasAttribute('aria-describedby')).toBe(false);
});

it('rebinds to the element for names in the shadow root it moves into', async () => {
  document.body.innerHTML = `
    <button id="invoker" type="button">Outside</button>
    <craft-tooltip for="invoker" trigger="click">Help</craft-tooltip>
    <section></section>`;
  const tooltip = document.querySelector('craft-tooltip')!;
  await tooltip.updateComplete;
  await after(0);
  const outside = document.querySelector('button')!;
  const root = document.querySelector('section')!.attachShadow({mode: 'open'});
  root.innerHTML = '<button id="invoker" type="button">Inside</button>';
  const inside = root.querySelector('button')!;

  root.append(tooltip);
  await after(50);

  expect(outside.hasAttribute('aria-describedby')).toBe(false);
  expect(inside.getAttribute('aria-describedby')).toBe(
    tooltip.querySelector('[slot="content"]')!.id
  );

  inside.click();
  await tooltip.updateComplete;
  expect(tooltip.open).toBe(true);
});

it('uses a slot="content" child as its content, keeping its id', async () => {
  document.body.innerHTML = `
    <button id="invoker" type="button">Info</button>
    <craft-tooltip for="invoker"><div slot="content" id="own">Help</div></craft-tooltip>`;
  const tooltip = document.querySelector('craft-tooltip')!;
  await tooltip.show();
  await after(50);
  const own = document.querySelector<HTMLElement>('#own')!;

  expect(tooltip.querySelectorAll('[slot="content"]')).toHaveLength(1);
  expect(own.getAttribute('role')).toBe('tooltip');
  expect(
    document.querySelector('button')!.getAttribute('aria-describedby')
  ).toBe('own');
});

it('moves beside an invoker slotted in while open', async () => {
  document.body.innerHTML = `
    <craft-tooltip placement="right" trigger="manual">Help</craft-tooltip>`;
  const tooltip = document.querySelector('craft-tooltip')!;
  await tooltip.show();
  await after(50);
  const invoker = document.createElement('button');
  invoker.type = 'button';
  invoker.slot = 'invoker';
  invoker.textContent = 'Late';
  invoker.style.margin = '200px 0 0 100px';

  tooltip.prepend(invoker);
  await after(50);

  const button = invoker.getBoundingClientRect();
  const content = tooltip
    .querySelector<HTMLElement>('[slot="content"]')!
    .getBoundingClientRect();

  expect(content.left - button.right).toBeGreaterThanOrEqual(0);
  expect(content.left - button.right).toBeLessThan(20);
  expect(content.top).toBeLessThan(button.bottom);
  expect(content.bottom).toBeGreaterThan(button.top);
});

it('follows a new invoker as its own scrolling container scrolls', async () => {
  document.body.innerHTML = `
    <div style="height: 100px; overflow: auto">
      <button id="first" type="button">First</button>
      <div style="height: 400px"></div>
    </div>
    <div id="scroller" style="height: 200px; overflow: auto">
      <div style="height: 100px"></div>
      <button id="second" type="button">Second</button>
      <div style="height: 400px"></div>
    </div>
    <craft-tooltip for="first" placement="right" trigger="manual">Help</craft-tooltip>`;
  const tooltip = document.querySelector('craft-tooltip')!;
  await tooltip.show();
  await after(50);

  tooltip.for = 'second';
  await tooltip.updateComplete;
  await after(50);
  document.querySelector('#scroller')!.scrollTop = 60;
  await after(50);

  const second = document.querySelector('#second')!.getBoundingClientRect();
  const content = tooltip
    .querySelector<HTMLElement>('[slot="content"]')!
    .getBoundingClientRect();

  expect(content.top).toBeLessThan(second.bottom);
  expect(content.bottom).toBeGreaterThan(second.top);
});

function expectBeside(content: HTMLElement, invoker: HTMLElement): void {
  const button = invoker.getBoundingClientRect();
  const rect = content.getBoundingClientRect();

  expect(content.checkVisibility()).toBe(true);
  expect(rect.left - button.right).toBeGreaterThanOrEqual(0);
  expect(rect.left - button.right).toBeLessThan(20);
  expect(rect.top).toBeLessThan(button.bottom);
  expect(rect.bottom).toBeGreaterThan(button.top);
}

it('hides while open if for names no element, and shows beside the next one it names', async () => {
  document.body.innerHTML = `
    <button id="first" type="button">First</button>
    <button id="second" type="button" style="margin: 200px 0 0 100px">Second</button>
    <craft-tooltip for="first" placement="right" trigger="manual">Help</craft-tooltip>`;
  const tooltip = document.querySelector('craft-tooltip')!;
  const content = tooltip.querySelector<HTMLElement>('[slot="content"]')!;
  await tooltip.show();
  await after(50);

  tooltip.for = 'missing';
  await tooltip.updateComplete;
  await after(50);

  expect(content.checkVisibility()).toBe(false);
  expect(tooltip.open).toBe(true);

  tooltip.for = 'second';
  await tooltip.updateComplete;
  await after(50);

  expectBeside(content, document.querySelector<HTMLElement>('#second')!);
});

it('hides while open once its slotted invoker is removed, and shows beside the next one', async () => {
  document.body.innerHTML = `
    <craft-tooltip placement="right" trigger="manual">
      <button slot="invoker" type="button">Old</button>
      Help
    </craft-tooltip>`;
  const tooltip = document.querySelector('craft-tooltip')!;
  const content = tooltip.querySelector<HTMLElement>('[slot="content"]')!;
  await tooltip.show();
  await after(50);

  tooltip.querySelector('button')!.remove();
  await after(50);

  expect(content.checkVisibility()).toBe(false);
  expect(tooltip.open).toBe(true);

  const replacement = document.createElement('button');
  replacement.type = 'button';
  replacement.slot = 'invoker';
  replacement.textContent = 'New';
  replacement.style.margin = '200px 0 0 100px';
  tooltip.prepend(replacement);
  await after(50);

  expectBeside(content, replacement);
});

it('stays hidden when shown while for names no element', async () => {
  document.body.innerHTML = `
    <craft-tooltip for="missing" trigger="manual">Help</craft-tooltip>`;
  const tooltip = document.querySelector('craft-tooltip')!;
  await tooltip.show();
  await after(50);

  expect(
    tooltip.querySelector<HTMLElement>('[slot="content"]')!.checkVisibility()
  ).toBe(false);
});

it('stays closed when craft-before-show is canceled, on hover or show()', async () => {
  const {userEvent} = await import('vite-plus/test/browser');
  const {invoker, tooltip} = await render();
  tooltip.addEventListener('craft-before-show', (event) =>
    event.preventDefault()
  );

  await userEvent.hover(invoker);
  await after(300);

  expect(tooltip.open).toBe(false);

  await tooltip.show();
  await after(50);

  expect(tooltip.open).toBe(false);
  expect(
    tooltip.querySelector<HTMLElement>('[slot="content"]')!.checkVisibility()
  ).toBe(false);
});

it('stays open when craft-before-hide is canceled, on hover out, Escape or hide()', async () => {
  const {userEvent} = await import('vite-plus/test/browser');
  const {invoker, elsewhere, tooltip} = await render();
  await tooltip.show();
  let requests = 0;
  tooltip.addEventListener('craft-before-hide', (event) => {
    requests++;
    event.preventDefault();
  });

  await userEvent.hover(invoker);
  await after(300);
  await userEvent.hover(elsewhere);
  await after(100);

  expect(tooltip.open).toBe(true);

  invoker.dispatchEvent(
    new KeyboardEvent('keyup', {key: 'Escape', bubbles: true})
  );
  await tooltip.updateComplete;

  expect(tooltip.open).toBe(true);

  await tooltip.hide();

  expect(tooltip.open).toBe(true);
  expect(requests).toBe(3);

  tooltip.open = false;
  await tooltip.updateComplete;

  expect(requests).toBe(3);
});

async function renderWithSuppliedContent(): Promise<{
  invoker: HTMLButtonElement;
  replacement: HTMLElement;
  tooltip: CraftTooltip;
}> {
  document.body.innerHTML = `
    <button id="invoker" type="button">Info</button>
    <craft-tooltip for="invoker" placement="right"><div slot="content" id="own">Help</div></craft-tooltip>
    <p style="margin-top: 200px">Elsewhere</p>`;
  const tooltip = document.querySelector('craft-tooltip')!;
  await tooltip.updateComplete;
  await after(0);
  const replacement = document.createElement('div');
  replacement.slot = 'content';
  replacement.id = 'replacement';
  replacement.textContent = 'New help';

  document.querySelector('#own')!.replaceWith(replacement);
  await after(50);

  return {
    invoker: document.querySelector('button')!,
    replacement,
    tooltip,
  };
}

it('points its invoker at a slot="content" child that replaces the supplied one', async () => {
  const {invoker, replacement} = await renderWithSuppliedContent();

  expect(invoker.getAttribute('aria-describedby')).toBe('replacement');
  expect(replacement.getAttribute('role')).toBe('tooltip');
});

it('hides once the pointer leaves a slot="content" child that replaced the supplied one', async () => {
  const {userEvent} = await import('vite-plus/test/browser');
  const {replacement, tooltip} = await renderWithSuppliedContent();
  await tooltip.show();
  await after(50);

  await userEvent.hover(replacement);
  await after(50);
  await userEvent.hover(document.querySelector('p')!);
  await after(100);

  expect(tooltip.open).toBe(false);
});

it('stays open for a keyboard-focused invoker after its content is replaced', async () => {
  const {userEvent} = await import('vite-plus/test/browser');
  const {invoker, elsewhere, tooltip} = await render();

  await userEvent.hover(elsewhere);
  await userEvent.tab();
  await after(300);
  expect(tooltip.open).toBe(true);

  const replacement = document.createElement('div');
  replacement.slot = 'content';
  replacement.textContent = 'New activity';
  tooltip.querySelector('[slot="content"]')!.replaceWith(replacement);
  await after(50);

  await userEvent.hover(invoker);
  await after(50);
  await userEvent.hover(elsewhere);
  await after(100);

  expect(document.activeElement).toBe(invoker);
  expect(tooltip.open).toBe(true);
});

it('still opens when its content is replaced while it waits to show', async () => {
  const {userEvent} = await import('vite-plus/test/browser');
  const {invoker, elsewhere, tooltip} = await render();

  const replacement = document.createElement('div');
  replacement.slot = 'content';
  replacement.textContent = 'New activity';
  // Replaced in the same task as the focus, so well within the show's delay.
  invoker.addEventListener(
    'focusin',
    () => tooltip.querySelector('[slot="content"]')!.replaceWith(replacement),
    {once: true}
  );

  await userEvent.hover(elsewhere);
  await userEvent.tab();
  await after(300);

  expect(document.activeElement).toBe(invoker);
  expect(tooltip.open).toBe(true);
  expect(invoker.getAttribute('aria-describedby')).toBe(replacement.id);
});

it('refers its invoker to content replaced while it was out of the document', async () => {
  const {invoker, tooltip} = await render();

  const replacement = document.createElement('div');
  replacement.slot = 'content';
  replacement.id = 'replacement';
  replacement.textContent = 'New activity';
  tooltip.remove();
  tooltip.querySelector('[slot="content"]')!.replaceWith(replacement);
  document.body.append(tooltip);
  await tooltip.updateComplete;
  await after(0);

  expect(invoker.getAttribute('aria-describedby')).toBe('replacement');
});
