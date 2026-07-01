import {describe, expect, it} from 'vitest';

import {VERSION} from '../src/index';

describe('@craftcms/garnish', () => {
  it('exposes a VERSION constant', () => {
    expect(VERSION).toBe('0.0.0');
  });

  it('runs in a DOM environment', () => {
    expect(typeof document).toBe('object');
  });
});
