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

  // Near the corner, well outside the icon itself.
  await userEvent.click(button, {
    position: {x: box.width - 2, y: box.height - 2},
  });
  await expect.poll(() => tooltip.open).toBe(true);
});
