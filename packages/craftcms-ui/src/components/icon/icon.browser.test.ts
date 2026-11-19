import {afterEach, expect, it} from 'vite-plus/test';
import './icon.js';

afterEach(() => {
  document.body.innerHTML = '';
  document.documentElement.removeAttribute('dir');
});

function scales(dir: 'ltr' | 'rtl', names: string[]): string[] {
  document.documentElement.dir = dir;
  document.body.innerHTML = names
    .map((name) => `<craft-icon name="${name}"></craft-icon>`)
    .join('');

  return [...document.querySelectorAll('craft-icon')].map(
    (icon) => getComputedStyle(icon).scale
  );
}

const mirrored = [
  'wrench',
  'tool',
  'arrow-up-right-from-square',
  'external',
  'check',
  'circle-check',
  'done',
  'square-check',
  'check-double',
  'magnifying-glass',
  'search',
];

it('mirrors icons that point a way in right-to-left layouts', () => {
  expect(scales('rtl', mirrored)).toEqual(mirrored.map(() => '-1 1'));
});

it('leaves them as they are in left-to-right layouts', () => {
  expect(scales('ltr', mirrored)).toEqual(mirrored.map(() => 'none'));
});

it('leaves arrows and chevrons alone, since they’re named for the way they point', () => {
  expect(scales('rtl', ['arrow-right', 'chevron-left', 'gear'])).toEqual([
    'none',
    'none',
    'none',
  ]);
});
