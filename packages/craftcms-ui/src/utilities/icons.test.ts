import {describe, expect, test} from 'vite-plus/test';
import {getIconUrl} from './icons.js';

describe('getIconUrl', () => {
  test.for([
    ['custom-icons/graphql', '/vendor/craft/icons/custom-icons/graphql.svg'],
    ['light/sliders', '/vendor/craft/icons/light/sliders.svg'],
    ['x', '/vendor/craft/icons/regular/x.svg'],
    ['newstamp', '/vendor/craft/icons/regular/newstamp.svg'],
  ])('%s', ([name, expected]) => {
    // console.log(args);
    expect(getIconUrl(name as string)).toBe(expected as string);
  });

  test('supports custom base URLs', () => {
    expect(
      getIconUrl('house', 'classic', 'solid', 'https://example.test')
    ).toBe('https://example.test/solid/house.svg');
  });
});
