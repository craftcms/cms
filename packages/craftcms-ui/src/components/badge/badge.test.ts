import {beforeEach, describe, expect, it} from 'vite-plus/test';

import './badge.js';
import type CraftBadge from './badge.js';

async function createBadge(
  attrs: Record<string, string> = {},
  innerHTML = 'Live'
): Promise<CraftBadge> {
  const element = document.createElement('craft-badge') as CraftBadge;
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

describe('craft-badge', () => {
  it('defaults to gray', async () => {
    const element = await createBadge();

    expect(element.fill).toBe('gray');
  });

  /**
   * The host's `data-color` is what scopes the `--c-color-*` tokens the badge's
   * own surface, border, and text read, so it has to follow `fill`.
   */
  it('reflects the fill onto data-color for the token scope', async () => {
    const element = await createBadge({fill: 'emerald'});

    expect(element.dataset.color).toBe('emerald');
  });

  it('keeps data-color in step when the fill changes', async () => {
    const element = await createBadge({fill: 'emerald'});

    element.fill = 'red';
    await element.updateComplete;

    expect(element.dataset.color).toBe('red');
  });

  /** The default prefix is an indicator tinted to match. */
  it('renders an indicator carrying the same fill', async () => {
    const element = await createBadge({fill: 'red'});
    const indicator = element.shadowRoot!.querySelector('craft-indicator');

    expect(indicator?.getAttribute('fill')).toBe('red');
  });

  it('exposes its regions as parts', async () => {
    const element = await createBadge();
    const parts = [...element.shadowRoot!.querySelectorAll('[part]')].map(
      (el) => el.getAttribute('part')
    );

    expect(parts).toEqual(
      expect.arrayContaining(['badge', 'prefix', 'indicator', 'suffix'])
    );
  });
});
