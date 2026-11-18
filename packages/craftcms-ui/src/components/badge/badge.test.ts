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

    expect(element.color).toBe('gray');
  });

  /**
   * The host's `data-color` is what scopes the `--c-color-*` tokens the badge's
   * own surface, border, and text read, so it has to follow `color`.
   */
  it('reflects the color onto data-color for the token scope', async () => {
    const element = await createBadge({color: 'emerald'});

    expect(element.dataset.color).toBe('emerald');
  });

  it('keeps data-color in step when the color changes', async () => {
    const element = await createBadge({color: 'emerald'});

    element.color = 'red';
    await element.updateComplete;

    expect(element.dataset.color).toBe('red');
  });

  /** The default prefix is an indicator tinted to match. */
  it('renders an indicator carrying the same color', async () => {
    const element = await createBadge({color: 'red'});
    const indicator = element.shadowRoot!.querySelector('craft-indicator');

    expect(indicator?.getAttribute('color')).toBe('red');
  });

  it('exposes its regions as parts', async () => {
    const element = await createBadge({}, 'Live<span slot="suffix">2</span>');
    const parts = [...element.shadowRoot!.querySelectorAll('[part]')].map(
      (el) => el.getAttribute('part')
    );

    expect(parts).toEqual(
      expect.arrayContaining(['badge', 'prefix', 'indicator', 'suffix'])
    );
  });

  /** Empty regions aren't rendered, so they take no space or gap. */
  it('leaves out the label slot and suffix while they have no content', async () => {
    const element = await createBadge({}, '');
    const root = element.shadowRoot!;

    expect(root.querySelector('slot:not([name])')).toBeNull();
    expect(root.querySelector('.badge__suffix')).toBeNull();
    expect(root.querySelector('.badge__prefix')).not.toBeNull();
  });

  it('leaves out the prefix with hide-prefix', async () => {
    const element = await createBadge({'hide-prefix': ''});

    expect(element.shadowRoot!.querySelector('.badge__prefix')).toBeNull();
  });

  /** The regions follow content added after the first render. */
  it('renders a region once content arrives for it', async () => {
    const element = await createBadge({}, '');
    const suffix = document.createElement('span');
    suffix.slot = 'suffix';
    suffix.textContent = '2';
    element.append('Live', suffix);
    await new Promise((resolve) => setTimeout(resolve));
    await element.updateComplete;
    const root = element.shadowRoot!;

    expect(root.querySelector('slot:not([name])')).not.toBeNull();
    expect(root.querySelector('.badge__suffix')).not.toBeNull();
  });
});
