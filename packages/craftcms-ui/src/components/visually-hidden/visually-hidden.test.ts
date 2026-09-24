import {beforeEach, describe, expect, it} from 'vite-plus/test';

import './visually-hidden.js';
import type CraftVisuallyHidden from './visually-hidden.js';

async function createVisuallyHidden(
  attrs: Record<string, string> = {},
  innerHTML = 'Skip to content'
): Promise<CraftVisuallyHidden> {
  const element = document.createElement(
    'craft-visually-hidden'
  ) as CraftVisuallyHidden;
  for (const [name, value] of Object.entries(attrs)) {
    element.setAttribute(name, value);
  }
  element.innerHTML = innerHTML;
  document.body.append(element);
  await element.updateComplete;
  return element;
}

beforeEach(() => {
  document.body.innerHTML = '';
});

describe('craft-visually-hidden', () => {
  /**
   * The content has to stay in the accessibility tree — hiding it with
   * `display: none` or `hidden` would take it out, which is the opposite of
   * what this component is for.
   */
  it('keeps its content in the DOM', async () => {
    const element = await createVisuallyHidden();

    expect(element.textContent).toBe('Skip to content');
    expect(element.shadowRoot!.querySelector('slot')).toBeTruthy();
    expect(element.hasAttribute('hidden')).toBe(false);
  });

  it('is not in debug mode by default', async () => {
    const element = await createVisuallyHidden();

    expect(element.debug).toBe(false);
  });

  /** `debug` reveals the content, for checking what a screen reader gets. */
  it('reflects debug so the stylesheet can reveal it', async () => {
    const element = await createVisuallyHidden();

    element.debug = true;
    await element.updateComplete;

    expect(element.hasAttribute('debug')).toBe(true);
  });
});
