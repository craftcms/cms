import {describe, expect, it} from 'vite-plus/test';

import {VERSION} from '../src/index';

describe('@craftcms/garnish', () => {
  it('exposes a VERSION constant', () => {
    expect(VERSION).toBe('0.0.0');
  });

  it('runs in a DOM environment', () => {
    expect(typeof document).toBe('object');
  });
});
