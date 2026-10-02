import {beforeEach, describe, expect, it} from 'vite-plus/test';

import './indicator.js';
import type CraftIndicator from './indicator.js';

async function createIndicator(
  attrs: Record<string, string> = {}
): Promise<CraftIndicator> {
  const element = document.createElement('craft-indicator') as CraftIndicator;
  for (const [name, value] of Object.entries(attrs)) {
    element.setAttribute(name, value);
  }
  document.body.append(element);
  await element.updateComplete;
  return element;
}

function dot(element: CraftIndicator): HTMLElement {
  return element.shadowRoot!.querySelector('.indicator')!;
}

beforeEach(() => {
  document.body.innerHTML = '';
});

describe('craft-indicator', () => {
  it('renders a dot', async () => {
    expect(dot(await createIndicator())).toBeTruthy();
  });

  /** A recognised variant resolves to that variant's fill token. */
  it('resolves a status variant to its token', async () => {
    const element = await createIndicator({fill: 'success'});

    expect(dot(element).getAttribute('style')).toContain(
      'var(--c-color-success-fill-loud)'
    );
  });

  /** So does a palette swatch, which is the same lookup. */
  it('resolves a palette swatch to its token', async () => {
    const element = await createIndicator({fill: 'red'});

    expect(dot(element).getAttribute('style')).toContain(
      'var(--c-color-red-fill-loud)'
    );
  });

  /** Anything else is passed through as a CSS colour. */
  it('passes an arbitrary colour straight through', async () => {
    const element = await createIndicator({fill: '#2c61de'});

    expect(dot(element).getAttribute('style')).toContain('#2c61de');
  });

  it('marks the outline appearance with a modifier class', async () => {
    const element = await createIndicator({appearance: 'outline'});

    expect(dot(element).classList.contains('indicator--outline')).toBe(true);
  });

  /**
   * An unlabelled dot is decoration beside the thing it marks, so it is not
   * announced as an unnamed image.
   */
  it('is not an image without a label', async () => {
    const element = await createIndicator();

    expect(dot(element).getAttribute('role')).toBeNull();
  });

  it('becomes a named image once it has a label', async () => {
    const element = await createIndicator({label: 'Online'});

    expect(dot(element).getAttribute('role')).toBe('img');
    expect(dot(element).getAttribute('aria-label')).toBe('Online');
  });
});
