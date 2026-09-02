import {beforeEach, describe, expect, it} from 'vite-plus/test';

import './badge-indicator.js';
import type CraftBadgeIndicator from './badge-indicator.js';

async function createBadgeIndicator(
  attrs: Record<string, string> = {}
): Promise<CraftBadgeIndicator> {
  const element = document.createElement(
    'craft-badge-indicator'
  ) as CraftBadgeIndicator;
  for (const [name, value] of Object.entries(attrs)) {
    element.setAttribute(name, value);
  }
  document.body.append(element);
  await element.updateComplete;
  return element;
}

function badge(element: CraftBadgeIndicator): HTMLElement {
  return element.shadowRoot!.querySelector('[part="badge"]')!;
}

function number(element: CraftBadgeIndicator): string | null {
  return (
    element.shadowRoot!.querySelector('.number')?.textContent?.trim() ?? null
  );
}

beforeEach(() => {
  document.body.innerHTML = '';
});

describe('craft-badge-indicator', () => {
  it('is a bare dot with no count', async () => {
    const element = await createBadgeIndicator();

    expect(number(element)).toBeNull();
    expect(
      badge(element).classList.contains('badge-indicator--with-number')
    ).toBe(false);
  });

  it('shows a count once there is one', async () => {
    const element = await createBadgeIndicator({'badge-count': '5'});

    expect(number(element)).toBe('5');
    expect(
      badge(element).classList.contains('badge-indicator--with-number')
    ).toBe(true);
  });

  /** A zero count is nothing to report, so it stays a dot. */
  it('stays a dot at zero', async () => {
    const element = await createBadgeIndicator({'badge-count': '0'});

    expect(number(element)).toBeNull();
  });

  /** Past 99 the exact number stops being useful and would not fit. */
  it('caps the count at 99+', async () => {
    expect(number(await createBadgeIndicator({'badge-count': '99'}))).toBe(
      '99'
    );
    expect(number(await createBadgeIndicator({'badge-count': '100'}))).toBe(
      '99+'
    );
  });

  it('carries a modifier class for each variant', async () => {
    for (const variant of ['secondary', 'inverse']) {
      const element = await createBadgeIndicator({variant});

      expect(
        badge(element).classList.contains(`badge-indicator--${variant}`)
      ).toBe(true);
    }
  });

  /**
   * Without alt text the indicator is decoration beside whatever it marks, so
   * it is not announced as an unnamed image.
   */
  it('is not an image without alt text', async () => {
    const element = await createBadgeIndicator({'badge-count': '5'});

    expect(badge(element).getAttribute('role')).toBeNull();
  });

  it('becomes a named image once it has alt text', async () => {
    const element = await createBadgeIndicator({'alt-text': 'Has updates'});

    expect(badge(element).getAttribute('role')).toBe('img');
    expect(badge(element).getAttribute('aria-labelledby')).toBe(
      `${element.id}-label`
    );
    expect(
      element.shadowRoot!.querySelector(`#${element.id}-label`)?.textContent
    ).toBe('Has updates');
  });

  it('gives each instance an id, so its label can be referenced', async () => {
    const first = await createBadgeIndicator();
    const second = await createBadgeIndicator();

    expect(first.id).not.toBe('');
    expect(first.id).not.toBe(second.id);
  });
});
