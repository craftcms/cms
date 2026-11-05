import {afterEach, expect, it} from 'vite-plus/test';

afterEach(() => {
  delete document.documentElement.dataset.theme;
});

it('sets the color scheme for the light theme, which has no data-theme', async () => {
  await import('../styles/cp.css');

  // Theme-aware SVG images follow their embedding element's color scheme, so
  // without this they'd follow a dark OS in the light theme.
  expect(getComputedStyle(document.documentElement).colorScheme).toBe('light');

  document.documentElement.dataset.theme = 'dark';
  expect(getComputedStyle(document.documentElement).colorScheme).toBe('dark');
});
