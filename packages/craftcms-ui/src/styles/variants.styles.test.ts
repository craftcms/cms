import {describe, expect, it} from 'vite-plus/test';
import variantsStyles from './variants.styles.js';

const css = variantsStyles.cssText;

describe('variant styles', () => {
  /**
   * `~=` throughout, so a multi-valued attribute (`variant="neutral outline"`)
   * still matches. `neutral` used to be the one exception.
   */
  it('matches variants among several', () => {
    expect(css).not.toMatch(/\[variant='/);
    expect(css).not.toMatch(/\[data-variant='/);
  });
});
