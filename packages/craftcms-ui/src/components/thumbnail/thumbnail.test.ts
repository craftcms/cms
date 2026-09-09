import {beforeEach, describe, expect, it} from 'vite-plus/test';
import './thumbnail.js';

beforeEach(() => {
  document.body.innerHTML = '';
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
    expect(element.checkered).toBe(true);
    expect(
      element.shadowRoot!.querySelector('.thumbnail--checkered')
    ).not.toBeNull();
    expect(image.getAttribute('src')).toBe('/thumbnail.jpg');
    expect(
      element.shadowRoot!.querySelector('[part="thumbnail"]')
    ).not.toBeNull();
  });

  it.each(['crop', 'fit', 'stretch', 'letterbox'] as const)(
    'accepts server-rendered %s attributes and reactive property changes',
    async (mode) => {
      document.body.innerHTML = `<craft-thumbnail mode="${mode}" src="/override.jpg" rounded checkered="false"></craft-thumbnail>`;
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
