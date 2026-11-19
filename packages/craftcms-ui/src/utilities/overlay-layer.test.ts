import {afterEach, describe, expect, it} from 'vite-plus/test';
import {placementForDirection} from './overlay-layer.js';

afterEach(() => {
  document.body.innerHTML = '';
});

function element(dir: 'ltr' | 'rtl'): HTMLElement {
  const el = document.createElement('div');
  el.style.direction = dir;
  document.body.append(el);
  return el;
}

describe('placementForDirection', () => {
  it('leaves placements as they are in left-to-right layouts', () => {
    const ltr = element('ltr');

    expect(
      ['bottom-start', 'top-end', 'right-start', 'left', 'bottom'].map((p) =>
        placementForDirection(p, ltr)
      )
    ).toEqual(['bottom-start', 'top-end', 'right-start', 'left', 'bottom']);
  });

  it('mirrors placements in right-to-left layouts', () => {
    const rtl = element('rtl');

    expect(
      [
        'bottom-start',
        'bottom-end',
        'top-start',
        'right',
        'right-start',
        'left-end',
        'bottom',
        'top',
      ].map((p) => placementForDirection(p, rtl))
    ).toEqual([
      'bottom-end',
      'bottom-start',
      'top-end',
      'left',
      'left-start',
      'right-end',
      'bottom',
      'top',
    ]);
  });
});
