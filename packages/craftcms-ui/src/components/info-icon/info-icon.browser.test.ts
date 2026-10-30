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
  await expect.poll(() => tooltip.opened).toBe(true);

  tooltip.dispatchEvent(
    new PointerEvent('pointerdown', {bubbles: true, composed: true})
  );
  await tooltip.updateComplete;
  expect(tooltip.opened).toBe(true);

  await userEvent.click(outside);
  await expect.poll(() => tooltip.opened).toBe(false);
});
