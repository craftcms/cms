import {beforeEach, describe, expect, it} from 'vite-plus/test';

import './spinner.js';
import type CraftSpinner from './spinner.js';

async function createSpinner(
  attrs: Record<string, string> = {},
  innerHTML = 'Loading'
): Promise<CraftSpinner> {
  const element = document.createElement('craft-spinner') as CraftSpinner;
  for (const [name, value] of Object.entries(attrs)) {
    element.setAttribute(name, value);
  }
  element.innerHTML = innerHTML;
  document.body.append(element);
  await element.updateComplete;
  return element;
}

function wrapper(element: CraftSpinner): HTMLElement {
  return element.shadowRoot!.querySelector('.wrapper')!;
}

beforeEach(() => {
  document.body.innerHTML = '';
});

describe('craft-spinner', () => {
  it('is visible by default', async () => {
    const element = await createSpinner();

    expect(element.visible).toBe(true);
    expect(wrapper(element).classList.contains('hidden')).toBe(false);
  });

  /** Hiding keeps it in the layout rather than collapsing the space. */
  it('hides without leaving the layout', async () => {
    const element = await createSpinner();

    element.hide();
    await element.updateComplete;

    expect(element.visible).toBe(false);
    expect(wrapper(element).classList.contains('hidden')).toBe(true);
    expect(wrapper(element).isConnected).toBe(true);
  });

  it('shows again', async () => {
    const element = await createSpinner({visible: 'false'});

    element.show();
    await element.updateComplete;

    expect(element.visible).toBe(true);
  });

  it('fires show and hide', async () => {
    const element = await createSpinner();
    const seen: string[] = [];
    element.addEventListener('craft-show', () => seen.push('show'));
    element.addEventListener('craft-hide', () => seen.push('hide'));

    element.hide();
    element.show();

    expect(seen).toEqual(['hide', 'show']);
  });

  /** The slotted text is the accessible name, and is visually hidden. */
  it('keeps its slotted label for assistive technology', async () => {
    const element = await createSpinner({}, 'Saving entry');

    expect(
      element.shadowRoot!.querySelector('.cp-visually-hidden')
    ).toBeTruthy();
    expect(element.textContent).toBe('Saving entry');
  });
});
