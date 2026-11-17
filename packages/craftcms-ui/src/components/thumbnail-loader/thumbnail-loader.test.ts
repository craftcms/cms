import {beforeEach, describe, expect, it} from 'vite-plus/test';

import './thumbnail-loader.js';

function create(innerHTML = ''): HTMLElement {
  const element = document.createElement('craft-thumbnail-loader');
  element.innerHTML = innerHTML;
  document.body.append(element);
  return element;
}

/** The element boots a frame at a time, so give it a few. */
async function frames(count = 3) {
  for (let frame = 0; frame < count; frame++) {
    await new Promise((resolve) => requestAnimationFrame(resolve));
  }
}

beforeEach(() => {
  document.body.innerHTML = '';
});

describe('craft-thumbnail-loader', () => {
  /**
   * A light-DOM controller: the loader scans and mutates the markup it wraps,
   * so a shadow root would put that markup out of its reach.
   */
  it('has no shadow root', async () => {
    const element = create('<div data-sizes="1x" data-src="a.png"></div>');
    await frames();

    expect(element.shadowRoot).toBeNull();
  });

  it('leaves the markup it wraps in place', async () => {
    const element = create('<div class="thumb" data-sizes="1x"></div>');
    await frames();

    expect(element.querySelector('.thumb')).toBeTruthy();
  });
});
