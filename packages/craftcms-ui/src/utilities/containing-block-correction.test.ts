import {expect, it, vi} from 'vite-plus/test';
import {containingBlockCorrection} from './containing-block-correction.js';

it('corrects viewport coordinates rebased by a containing block', () => {
  const popper = document.createElement('div');
  popper.style.left = '100px';
  popper.style.top = '200px';
  vi.spyOn(popper, 'getBoundingClientRect').mockReturnValue({
    x: 120,
    y: 230,
  } as DOMRect);

  containingBlockCorrection.fn({state: {elements: {popper}}});

  expect(popper.style.left).toBe('80px');
  expect(popper.style.top).toBe('170px');
});
