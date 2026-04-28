import {describe, test, expect} from 'vitest';
import {getIconUrl} from './icons.js';

describe('getIconUrl', () => {
  test.for([
    ['custom-icons/graphql', '/vendor/craft/icons/custom-icons/graphql.svg'],
    ['light/sliders', '/vendor/craft/icons/light/sliders.svg'],
    ['x', '/vendor/craft/icons/regular/x.svg'],
    ['newstamp', '/vendor/craft/icons/regular/certificate.svg'],
  ])('%s', ([name, expected]) => {
    // console.log(args);
    expect(getIconUrl(name as string)).toBe(expected as string);
  });
});
