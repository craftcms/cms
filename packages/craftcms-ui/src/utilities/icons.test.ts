import {describe, expect, test} from 'vite-plus/test';
import {getIconUrl} from './icons.js';

describe('getIconUrl', () => {
  test.for([
    ['custom-icons/graphql', '/vendor/craft/icons/custom-icons/graphql.svg'],
    ['light/sliders', '/vendor/craft/icons/light/sliders.svg'],
    ['x', '/vendor/craft/icons/regular/x.svg'],
    // Pre-6 names resolve to whatever replaced them.
    ['newstamp', '/vendor/craft/icons/regular/certificate.svg'],
    ['view', '/vendor/craft/icons/regular/eye.svg'],
    // Icons that only ship as custom icons land in `custom-icons`, even though
    // nothing asked for that variant...
    ['duplicate', '/vendor/craft/icons/custom-icons/duplicate.svg'],
    // ...including when the alias is what makes them custom-only.
    ['shareleft', '/vendor/craft/icons/custom-icons/share-flip.svg'],
    // A name that exists in both keeps resolving to the Font Awesome copy.
    ['move', '/vendor/craft/icons/regular/grip-dots.svg'],
  ])('%s', ([name, expected]) => {
    // console.log(args);
    expect(getIconUrl(name as string)).toBe(expected as string);
  });

  test('aliases and custom-icon routing survive an explicit variant', () => {
    expect(getIconUrl('view', 'classic', 'solid')).toBe(
      '/vendor/craft/icons/solid/eye.svg'
    );
    expect(getIconUrl('duplicate', 'classic', 'solid')).toBe(
      '/vendor/craft/icons/custom-icons/duplicate.svg'
    );
  });

  test('supports custom base URLs', () => {
    expect(
      getIconUrl('house', 'classic', 'solid', 'https://example.test')
    ).toBe('https://example.test/solid/house.svg');
  });
});
