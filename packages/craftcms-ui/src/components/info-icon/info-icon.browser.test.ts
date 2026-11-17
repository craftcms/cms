import {beforeEach, expect, it} from 'vite-plus/test';
import type CraftTooltip from '../tooltip/tooltip.js';
import type CraftInfoIcon from './info-icon.js';
import './info-icon.js';

beforeEach(() => {
  document.body.innerHTML = '';
});

it('hides its tooltip on a click outside it, but not on one inside it', async () => {
  const {userEvent} = await import('vite-plus/test/browser');
  const outside = document.createElement('p');
  outside.textContent = 'Elsewhere on the page';
  const element = document.createElement('craft-info-icon') as CraftInfoIcon;
  element.textContent = 'Shown in the entry index.';
  document.body.append(outside, element);
  await element.updateComplete;

  const tooltip =
    element.shadowRoot!.querySelector<CraftTooltip>('craft-tooltip')!;
  const button = element.shadowRoot!.querySelector('craft-button')!;

  await userEvent.click(button);
  await expect.poll(() => tooltip.open).toBe(true);

  tooltip.dispatchEvent(
    new PointerEvent('pointerdown', {bubbles: true, composed: true})
  );
  await tooltip.updateComplete;
  expect(tooltip.open).toBe(true);

  await userEvent.click(outside);
  await expect.poll(() => tooltip.open).toBe(false);
});

it('gives the button a target of at least 24×24 CSS px, all of which opens it', async () => {
  const {userEvent} = await import('vite-plus/test/browser');
  await import('../../styles/cp.css');
  const element = document.createElement('craft-info-icon') as CraftInfoIcon;
  element.textContent = 'Shown in the entry index.';
  document.body.append(element);
  await element.updateComplete;

  const tooltip =
    element.shadowRoot!.querySelector<CraftTooltip>('craft-tooltip')!;
  const button = element.shadowRoot!.querySelector('craft-button')!;
  const box = button.getBoundingClientRect();

  expect(box.width).toBeGreaterThanOrEqual(24);
  expect(box.height).toBeGreaterThanOrEqual(24);

  // Playwright measures `position` from inside the border, so these are the
  // last pixel of the 24px box on the right and at the bottom.
  for (const position of [
    {x: button.clientWidth, y: button.clientHeight / 2},
    {x: button.clientWidth / 2, y: button.clientHeight},
  ]) {
    await userEvent.click(button, {position});
    await expect.poll(() => tooltip.open).toBe(true);
    await userEvent.keyboard('{Escape}');
    await expect.poll(() => tooltip.open).toBe(false);
  }
});
