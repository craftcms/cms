import $ from 'jquery';
import {afterEach, beforeEach, expect, it, vi} from 'vite-plus/test';

const {load} = vi.hoisted(() => ({load: vi.fn()}));

vi.mock('@craftcms/ui/utilities/thumbnail-loader', () => ({
  ThumbnailLoader: class {
    load(...args: unknown[]): void {
      load(...args);
    }
  },
}));

beforeEach(() => {
  vi.resetModules();
  vi.useFakeTimers();
  load.mockReset();
  vi.stubGlobal('$', Object.assign($, {isTouchCapable: () => false}));
  vi.stubGlobal('Craft', {
    remainingSessionTime: 0,
    AnimationBlocker: class {},
    getLocalStorage: () => undefined,
  });
  const extend = (members: object): any => {
    class LegacyClass {
      addListener = vi.fn();
      on = vi.fn();

      constructor() {
        (this as any).init?.();
      }
    }
    Object.defineProperties(
      LegacyClass.prototype,
      Object.getOwnPropertyDescriptors(members)
    );
    return Object.assign(LegacyClass, {extend});
  };
  vi.stubGlobal('Garnish', {
    Base: {extend},
    $doc: {ready: vi.fn()},
    isMobileBrowser: () => false,
    uiLayerManager: {on: vi.fn()},
  });
  document.body.innerHTML = '<main id="page-container"></main>';
});

afterEach(() => {
  vi.useRealTimers();
  vi.unstubAllGlobals();
  delete ($ as any).isTouchCapable;
  document.body.replaceChildren();
});

async function initializeCp(): Promise<void> {
  await import('../../../../packages/craftcms-legacy/cp/src/js/CP.js');
}

it('waits for the thumbnail module even when it loads after the old 500 ms deadline', async () => {
  await initializeCp();
  expect(() => vi.advanceTimersByTime(5_000)).not.toThrow();
  expect(load).not.toHaveBeenCalled();

  await import('./index');

  expect(load).toHaveBeenCalledExactlyOnceWith(
    document.querySelector('#page-container'),
    '.thumb[data-sizes]'
  );
});

it('loads thumbnails when the module is registered before CP initialization', async () => {
  await import('./index');
  await initializeCp();

  expect(load).toHaveBeenCalledExactlyOnceWith(
    document.querySelector('#page-container'),
    '.thumb[data-sizes]'
  );
});
