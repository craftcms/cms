import {afterEach, beforeEach, expect, it, vi} from 'vite-plus/test';
import {getActionUrl, getCpUrl, getUrl} from './url';

beforeEach(() => {
  vi.stubGlobal('Craft', {
    baseUrl: 'https://craft.test/',
    baseCpUrl: 'https://craft.test/admin',
    actionUrl: 'https://craft.test/index.php?p=actions',
  });
});

afterEach(() => {
  vi.unstubAllGlobals();
});

it.each([
  ['blog/', undefined, 'https://craft.test/blog'],
  [
    'blog?page=2#top',
    {sort: 'asc'},
    'https://craft.test/blog?page=2&sort=asc#top',
  ],
  ['blog#old', {'#': 'new'}, 'https://craft.test/blog#new'],
  ['blog', 'tag=a b#tags', 'https://craft.test/blog?tag=a%20b#tags'],
  ['blog', {ids: [1, 2]}, 'https://craft.test/blog?ids%5B%5D=1&ids%5B%5D=2'],
  ['/already/absolute', {a: 1}, '/already/absolute?a=1'],
  ['https://elsewhere.test/x', undefined, 'https://elsewhere.test/x'],
])('builds %s with %o', (path, params, expected) => {
  expect(getUrl(path, params)).toBe(expected);
});

it('keeps the base URL’s own query string', () => {
  expect(getActionUrl('elements/edit', {elementId: 5})).toBe(
    'https://craft.test/index.php/elements/edit?p=actions&elementId=5'
  );
});

it('requires a control panel base URL', () => {
  expect(getCpUrl('entries')).toBe('https://craft.test/admin/entries');

  vi.stubGlobal('Craft', {});

  expect(() => getCpUrl('entries')).toThrow('Craft.baseCpUrl is undefined.');
});
