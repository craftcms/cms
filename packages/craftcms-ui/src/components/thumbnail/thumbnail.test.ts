import {beforeEach, describe, expect, it} from 'vite-plus/test';

import './thumbnail.js';
import type CraftThumbnail from './thumbnail.js';

async function createThumbnail(
  attrs: Record<string, string> = {},
  innerHTML = ''
): Promise<CraftThumbnail> {
  const element = document.createElement('craft-thumbnail') as CraftThumbnail;
  for (const [name, value] of Object.entries(attrs)) {
    element.setAttribute(name, value);
  }
  element.innerHTML = innerHTML;
  document.body.append(element);
  await element.updateComplete;
  return element;
}

function box(element: CraftThumbnail): HTMLElement {
  return element.shadowRoot!.querySelector('.thumbnail')!;
}

function image(element: CraftThumbnail): HTMLImageElement | null {
  return element.shadowRoot!.querySelector('img');
}

beforeEach(() => {
  document.body.innerHTML = '';
});

describe('craft-thumbnail', () => {
  it('renders an image from src', async () => {
    const element = await createThumbnail({src: 'a.png', alt: 'An asset'});

    expect(image(element)?.getAttribute('src')).toBe('a.png');
    expect(image(element)?.getAttribute('alt')).toBe('An asset');
  });

  it('lazy-loads by default, and defers decoding', async () => {
    const element = await createThumbnail({src: 'a.png'});

    expect(image(element)?.getAttribute('loading')).toBe('lazy');
    expect(image(element)?.getAttribute('decoding')).toBe('async');
  });

  it('takes an eager loading strategy when asked', async () => {
    const element = await createThumbnail({src: 'a.png', loading: 'eager'});

    expect(image(element)?.getAttribute('loading')).toBe('eager');
  });

  it('falls back to the default slot with no src', async () => {
    const element = await createThumbnail({}, '<svg></svg>');

    expect(image(element)).toBeNull();
    expect(element.shadowRoot!.querySelector('slot')).toBeTruthy();
  });

  /**
   * `checkered` is an ordinary boolean attribute: absent means off. The server
   * renders it only for images that can be transparent, and omits it
   * otherwise, so a defaulted-on property would checker every other thumbnail.
   */
  it('is not checkered unless asked', async () => {
    const element = await createThumbnail({src: 'a.png'});

    expect(element.checkered).toBe(false);
    expect(box(element).classList.contains('thumbnail--checkered')).toBe(false);
  });

  it('checkers when the attribute is present', async () => {
    const element = await createThumbnail({src: 'a.png', checkered: ''});

    expect(element.checkered).toBe(true);
    expect(box(element).classList.contains('thumbnail--checkered')).toBe(true);
  });

  it('rounds when the attribute is present', async () => {
    const element = await createThumbnail({src: 'a.png', rounded: ''});

    expect(box(element).classList.contains('thumbnail--rounded')).toBe(true);
  });

  it('passes the responsive image attributes through', async () => {
    const element = await createThumbnail({
      src: 'a.png',
      srcset: 'a.png 1x, a@2x.png 2x',
      sizes: '2rem',
      width: '32',
      height: '32',
    });

    expect(image(element)?.getAttribute('srcset')).toBe(
      'a.png 1x, a@2x.png 2x'
    );
    expect(image(element)?.getAttribute('sizes')).toBe('2rem');
    expect(image(element)?.getAttribute('width')).toBe('32');
  });
});
