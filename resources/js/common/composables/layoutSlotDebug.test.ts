import {afterEach, describe, expect, it} from 'vite-plus/test';
import {layoutSlotDebugEnabled, resetLayoutSlotDebug} from './layoutSlotDebug';

function visit(search: string): void {
  window.history.replaceState(null, '', `/admin${search}`);
  resetLayoutSlotDebug();
}

afterEach(() => {
  window.localStorage.clear();
  visit('');
});

describe('layoutSlotDebugEnabled', () => {
  it('is off by default', () => {
    visit('');

    expect(layoutSlotDebugEnabled()).toBe(false);
  });

  it('turns on from the query string and stays on', () => {
    visit('?debug-slots=0');
    layoutSlotDebugEnabled();

    visit('?debug-slots');
    expect(layoutSlotDebugEnabled()).toBe(true);

    visit('');
    expect(layoutSlotDebugEnabled()).toBe(true);
  });

  it('turns off with debug-slots=0', () => {
    visit('?debug-slots');
    layoutSlotDebugEnabled();

    visit('?debug-slots=0');
    expect(layoutSlotDebugEnabled()).toBe(false);

    visit('');
    expect(layoutSlotDebugEnabled()).toBe(false);
  });
});
