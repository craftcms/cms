import {afterEach, expect, it} from 'vite-plus/test';
import {trackScrollbarWidth} from './scrollbarWidth';

afterEach(() => {
  document.documentElement.style.removeProperty('--cp-scrollbar-width');
  document.documentElement.style.removeProperty('overflow-y');
});

it('sets --cp-scrollbar-width to the width the page scrollbar takes', () => {
  document.documentElement.style.overflowY = 'scroll';
  const pageScrollbar =
    window.innerWidth - document.documentElement.clientWidth;

  trackScrollbarWidth();

  expect(
    document.documentElement.style.getPropertyValue('--cp-scrollbar-width')
  ).toBe(`${pageScrollbar}px`);
});
