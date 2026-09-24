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

  /**
   * The element can upgrade before its children have parsed — during the
   * initial HTML parse, or when a fragment is injected — so it waits rather
   * than giving up on an empty subtree.
   */
  it('waits for thumb markup that arrives after it connects', async () => {
    const element = create();
    await frames(1);

    const thumb = document.createElement('div');
    thumb.dataset.sizes = '1x';
    thumb.dataset.src = 'a.png';
    element.append(thumb);
    await frames();

    expect(element.querySelector('[data-sizes]')).toBe(thumb);
  });

  /** Disconnecting has to destroy the loader's workers and listeners. */
  it('survives being removed and re-added', async () => {
    const element = create('<div data-sizes="1x" data-src="a.png"></div>');
    await frames();

    element.remove();
    await frames(1);
    document.body.append(element);
    await frames();

    expect(element.isConnected).toBe(true);
  });
});
