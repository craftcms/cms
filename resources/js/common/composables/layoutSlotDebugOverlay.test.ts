import {afterEach, describe, expect, it} from 'vite-plus/test';
import {trackLayoutSlotDebugRegion} from './layoutSlotDebugOverlay';

function layer(): Element | null {
  return document.querySelector('.layout-slot-debug-layer');
}

afterEach(() => {
  document.body.innerHTML = '';
});

describe('trackLayoutSlotDebugRegion', () => {
  it('adds a hidden label for the region to a shared layer', () => {
    const untrackTitle = trackLayoutSlotDebugRegion(
      document.createElement('div'),
      'title'
    );
    const untrackToolbar = trackLayoutSlotDebugRegion(
      document.createElement('div'),
      'toolbar'
    );

    const labels = [...layer()!.querySelectorAll('span')];

    expect(labels.map((label) => label.textContent)).toEqual([
      'title',
      'toolbar',
    ]);
    expect(labels.every((label) => label.hidden)).toBe(true);
    expect(layer()!.getAttribute('aria-hidden')).toBe('true');

    untrackTitle();
    untrackToolbar();
  });

  it('removes the layer once the last region is gone', () => {
    const untrackTitle = trackLayoutSlotDebugRegion(
      document.createElement('div'),
      'title'
    );
    const untrackToolbar = trackLayoutSlotDebugRegion(
      document.createElement('div'),
      'toolbar'
    );

    untrackTitle();
    expect(layer()!.querySelectorAll('span')).toHaveLength(1);

    untrackToolbar();
    expect(layer()).toBeNull();
  });
});
