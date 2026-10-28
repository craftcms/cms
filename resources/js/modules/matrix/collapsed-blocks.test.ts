import {afterEach, describe, expect, it} from 'vite-plus/test';
import {
  isBlockCollapsed,
  rememberCollapsedBlock,
  forgetCollapsedBlock,
} from '@/modules/matrix/collapsed-blocks';

const LEGACY_KEY = 'Craft-.MatrixInput.collapsedEntries';

describe('collapsed blocks', () => {
  afterEach(() => {
    localStorage.clear();
  });

  it('remembers collapsed blocks per site', () => {
    rememberCollapsedBlock(10, 1);

    expect(isBlockCollapsed(10, 1)).toBe(true);
    expect(isBlockCollapsed(10, 2)).toBe(false);
  });

  it('honors blocks collapsed before state was kept per site', () => {
    localStorage.setItem(LEGACY_KEY, '10,11');

    expect(isBlockCollapsed(10, 1)).toBe(true);
    expect(isBlockCollapsed(11, 2)).toBe(true);
  });

  it('keeps other sites on the legacy state once one site changes', () => {
    localStorage.setItem(LEGACY_KEY, '10,11');

    forgetCollapsedBlock(10, 1);

    expect(isBlockCollapsed(10, 1)).toBe(false);
    expect(isBlockCollapsed(11, 1)).toBe(true);
    expect(isBlockCollapsed(10, 2)).toBe(true);
  });
});
