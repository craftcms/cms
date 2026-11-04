import {beforeEach, describe, expect, it} from 'vite-plus/test';

import './switch-button.js';
import type CraftSwitchButton from './switch-button.js';

async function createSwitchButton(
  attrs: Record<string, string> = {}
): Promise<CraftSwitchButton> {
  const element = document.createElement(
    'craft-switch-button'
  ) as CraftSwitchButton;
  for (const [name, value] of Object.entries(attrs)) {
    element.setAttribute(name, value);
  }
  document.body.append(element);
  await element.updateComplete;
  return element;
}

beforeEach(() => {
  document.body.innerHTML = '';
});

describe('craft-switch-button', () => {
  /**
   * The button is the toggle a `craft-switch` renders, and it reports its own
   * state — the three values `aria-checked` can take are the whole contract.
   */
  it('reports the off state', async () => {
    const element = await createSwitchButton();

    expect(element.getAttribute('aria-checked')).toBe('false');
  });

  it('reports the on state', async () => {
    const element = await createSwitchButton();

    element.checked = true;
    await element.updateComplete;

    expect(element.getAttribute('aria-checked')).toBe('true');
  });

  /** Mixed is display-only here; `craft-switch` owns the transitions. */
  it('reports the mixed state', async () => {
    const element = await createSwitchButton({indeterminate: ''});

    expect(element.getAttribute('aria-checked')).toBe('mixed');
  });

  /** Checked wins, so a switch cannot claim to be both on and mixed. */
  it('prefers checked over mixed', async () => {
    const element = await createSwitchButton({indeterminate: ''});

    element.checked = true;
    await element.updateComplete;

    expect(element.getAttribute('aria-checked')).toBe('true');
  });

  it('reflects indeterminate so the stylesheet can center the thumb', async () => {
    const element = await createSwitchButton({indeterminate: ''});

    expect(element.indeterminate).toBe(true);
    expect(element.hasAttribute('indeterminate')).toBe(true);
  });
});

describe('craft-switch-button keyboard and state', () => {
  it('toggles on Space and Enter release, without scrolling on Space', async () => {
    const element = await createSwitchButton();
    const changes: boolean[] = [];
    element.addEventListener('change', () => changes.push(element.checked));

    const space = new KeyboardEvent('keydown', {key: ' ', cancelable: true});
    element.dispatchEvent(space);
    expect(space.defaultPrevented).toBe(true);

    element.dispatchEvent(new KeyboardEvent('keyup', {key: ' '}));
    element.dispatchEvent(new KeyboardEvent('keyup', {key: 'Enter'}));

    expect(changes).toEqual([true, false]);
  });

  it('is silent when checked from code', async () => {
    const element = await createSwitchButton();
    const seen: string[] = [];
    element.addEventListener('change', () => seen.push('change'));

    element.checked = true;
    await element.updateComplete;

    expect(seen).toEqual([]);
    expect(element.getAttribute('aria-checked')).toBe('true');
  });

  it('leaves the Tab order and ignores toggles while disabled', async () => {
    const element = await createSwitchButton({disabled: ''});

    expect(element.getAttribute('aria-disabled')).toBe('true');
    expect(element.tabIndex).toBe(-1);

    element.click();
    expect(element.checked).toBe(false);

    element.disabled = false;
    await element.updateComplete;
    expect(element.tabIndex).toBe(0);
  });
});
