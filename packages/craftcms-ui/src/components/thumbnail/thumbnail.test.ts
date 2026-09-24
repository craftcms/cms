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

describe('craft-thumbnail modes', () => {
  it('defaults to fit without changing the image or wrapper contract', async () => {
    const element = document.createElement('craft-thumbnail');
    element.src = '/thumbnail.jpg';
    element.srcset = '/thumbnail.jpg 120w, /thumbnail-2x.jpg 240w';
    element.sizes = 'calc(120rem/16)';
    element.alt = 'Accessible thumbnail';
    document.body.append(element);
    await element.updateComplete;

    const image = element.shadowRoot!.querySelector('img')!;
    expect(element.mode).toBe('fit');
    expect(element.getAttribute('mode')).toBe('fit');
    expect(image.getAttribute('alt')).toBe('Accessible thumbnail');
    expect(image.getAttribute('srcset')).toBe(element.srcset);
    expect(image.getAttribute('sizes')).toBe(element.sizes);
    expect(image.getAttribute('loading')).toBe('lazy');
    expect(image.getAttribute('decoding')).toBe('async');
    expect(element.checkered).toBe(false);
    expect(
      element.shadowRoot!.querySelector('.thumbnail--checkered')
    ).toBeNull();
    expect(image.getAttribute('src')).toBe('/thumbnail.jpg');
    expect(
      element.shadowRoot!.querySelector('[part="thumbnail"]')
    ).not.toBeNull();
  });

  it.each(['crop', 'fit', 'stretch', 'letterbox'] as const)(
    'accepts server-rendered %s attributes and reactive property changes',
    async (mode) => {
      document.body.innerHTML = `<craft-thumbnail mode="${mode}" src="/override.jpg" rounded></craft-thumbnail>`;
      const element = document.querySelector('craft-thumbnail')!;
      await element.updateComplete;

      expect(element.mode).toBe(mode);
      expect(element.getAttribute('mode')).toBe(mode);
      expect(element.checkered).toBe(false);
      expect(element.rounded).toBe(true);
      expect(
        element.shadowRoot!.querySelector('img')!.getAttribute('src')
      ).toBe('/override.jpg');

      element.mode = 'fit';
      await element.updateComplete;
      expect(element.mode).toBe('fit');
      expect(element.getAttribute('mode')).toBe('fit');
      expect(
        element.shadowRoot!.querySelector('img')!.getAttribute('src')
      ).toBe('/override.jpg');
    }
  );

  it('renders direct slotted content without a duplicate internal image', async () => {
    const element = document.createElement('craft-thumbnail');
    element.innerHTML = '<img src="/slotted.jpg" alt="Slotted thumbnail">';
    document.body.append(element);
    await element.updateComplete;

    expect(element.shadowRoot!.querySelector('img')).toBeNull();
    expect(
      element.shadowRoot!.querySelector('slot')!.assignedElements()
    ).toEqual([element.firstElementChild]);
  });

  it('fits direct SVG viewports and restores authored aspect ratios when returning to containment', async () => {
    const element = document.createElement('craft-thumbnail');
    element.innerHTML =
      '<svg viewBox="0 0 300 150" preserveAspectRatio="xMinYMin meet"></svg><div><svg viewBox="0 0 300 150"></svg></div>';
    document.body.append(element);
    await element.updateComplete;
    const svg = element.querySelector('svg')!;

    for (const [mode, aspectRatio] of [
      ['crop', 'xMidYMid slice'],
      ['stretch', 'none'],
      ['letterbox', 'xMinYMin meet'],
    ] as const) {
      element.mode = mode;
      await element.updateComplete;
      expect(svg.getAttribute('preserveAspectRatio')).toBe(aspectRatio);
      expect(
        element.querySelector('div svg')!.hasAttribute('preserveAspectRatio')
      ).toBe(false);
    }

    element.mode = 'crop';
    await element.updateComplete;
    element.remove();
    expect(svg.getAttribute('preserveAspectRatio')).toBe('xMinYMin meet');
    document.body.append(element);
    expect(svg.getAttribute('preserveAspectRatio')).toBe('xMidYMid slice');
  });

  it('applies modes to newly assigned SVGs and restores removed SVGs', async () => {
    const element = document.createElement('craft-thumbnail');
    element.mode = 'crop';
    document.body.append(element);
    await element.updateComplete;
    element.innerHTML = '<svg viewBox="0 0 300 150"></svg>';
    const slot = element.shadowRoot!.querySelector('slot')!;
    slot.dispatchEvent(new Event('slotchange'));
    const svg = element.querySelector('svg')!;
    expect(svg.getAttribute('preserveAspectRatio')).toBe('xMidYMid slice');

    svg.remove();
    slot.dispatchEvent(new Event('slotchange'));
    expect(svg.hasAttribute('preserveAspectRatio')).toBe(false);
  });
});

describe('craft-thumbnail animated', () => {
  it('does not freeze by default', async () => {
    const element = document.createElement('craft-thumbnail');
    element.src = '/thumbnail.jpg';
    document.body.append(element);
    await element.updateComplete;

    expect(element.animated).toBe(false);
    expect(
      element.shadowRoot!.querySelector('canvas[part="cover"]')
    ).toBeNull();
  });

  it('freezes when the animated attribute is set, without altering the image', async () => {
    document.body.innerHTML =
      '<craft-thumbnail src="/thumbnail.jpg" alt="A thumbnail" animated></craft-thumbnail>';
    const element = document.querySelector('craft-thumbnail')!;
    await element.updateComplete;

    const canvas = element.shadowRoot!.querySelector('canvas[part="cover"]');
    expect(canvas).not.toBeNull();
    expect(canvas!.getAttribute('aria-hidden')).toBe('true');
    expect(canvas!.getAttribute('role')).toBe('presentation');

    const image = element.shadowRoot!.querySelector('img')!;
    expect(image.classList.contains('thumbnail__image')).toBe(true);
    expect(image.getAttribute('alt')).toBe('A thumbnail');
  });

  it.each([
    ['a .gif src', '/thumbnail.gif'],
    ['a .webp src', '/thumbnail.webp'],
    ['a query-stringed .gif src', '/thumbnail.gif?v=2'],
  ])(
    'freezes based on %s, without the animated attribute',
    async (_label, src) => {
      const element = document.createElement('craft-thumbnail');
      element.src = src;
      document.body.append(element);
      await element.updateComplete;

      expect(element.animated).toBe(false);
      expect(
        element.shadowRoot!.querySelector('canvas[part="cover"]')
      ).not.toBeNull();
    }
  );

  it('does not mistake a similarly-named extension for an animated one', async () => {
    const element = document.createElement('craft-thumbnail');
    element.src = '/thumbnail.gifted.jpg';
    document.body.append(element);
    await element.updateComplete;

    expect(
      element.shadowRoot!.querySelector('canvas[part="cover"]')
    ).toBeNull();
  });
});
