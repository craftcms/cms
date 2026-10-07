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

  /** A recognized variant resolves to that variant's fill token. */
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

  /** Anything else is passed through as a CSS color. */
  it('passes an arbitrary color straight through', async () => {
    const element = await createIndicator({fill: '#2c61de'});

    expect(dot(element).getAttribute('style')).toContain('#2c61de');
  });

  it('marks the outline appearance with a modifier class', async () => {
    const element = await createIndicator({appearance: 'outline'});

    expect(dot(element).classList.contains('indicator--outline')).toBe(true);
  });

  /** White and black dots keep an outline by default, so they stay visible. */
  it.each([
    ['red', 'indicator--solid'],
    ['white', 'indicator--outline-fill'],
    ['black', 'indicator--outline-fill'],
  ])('defaults a %s fill to the %s appearance', async (fill, modifier) => {
    const element = await createIndicator({fill});

    expect(dot(element).classList.contains(modifier)).toBe(true);
  });

  it('keeps an appearance that was set explicitly', async () => {
    const element = await createIndicator({fill: 'black', appearance: 'solid'});

    expect(dot(element).classList.contains('indicator--solid')).toBe(true);
    expect(dot(element).classList.contains('indicator--outline-fill')).toBe(
      false
    );
  });

  /** A black dot's outline is white, since a dark one would vanish. */
  it('marks a black fill so its outline can be white', async () => {
    const element = await createIndicator({fill: 'black'});

    expect(dot(element).classList.contains('indicator--black')).toBe(true);
  });

  /**
   * An unlabeled dot is decoration beside the thing it marks, so it is not
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
