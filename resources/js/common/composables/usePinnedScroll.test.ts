import {effectScope, nextTick, shallowRef, type EffectScope} from 'vue';
import {afterEach, beforeEach, expect, it, vi} from 'vite-plus/test';
import {usePinnedScroll} from './usePinnedScroll';

let resize: () => void;
let scope: EffectScope;

beforeEach(() => {
  vi.stubGlobal(
    'ResizeObserver',
    class {
      constructor(callback: ResizeObserverCallback) {
        resize = () => callback([], this as ResizeObserver);
      }

      observe() {}

      unobserve() {}

      disconnect() {}
    }
  );
  vi.stubGlobal('requestAnimationFrame', (callback: () => void) => callback());
});

afterEach(() => {
  scope.stop();
  vi.unstubAllGlobals();
});

/** A 100px tall scroller whose content height the test controls. */
async function setUp(options?: {pinned?: boolean}) {
  const element = document.createElement('div');
  let height = 100;
  let scrollTop = 0;
  Object.defineProperty(element, 'clientHeight', {value: 100});
  Object.defineProperty(element, 'scrollHeight', {get: () => height});
  Object.defineProperty(element, 'scrollTop', {
    get: () => scrollTop,
    set: (value: number) => {
      scrollTop = Math.max(0, Math.min(value, height - 100));
    },
  });

  scope = effectScope();
  const pinned = scope.run(() =>
    usePinnedScroll(shallowRef(element), element, options)
  )!;
  await nextTick();

  return {
    pinned,
    element,
    /** Grows the content, as rendering does, and with `observed`, as the resize is observed. */
    grow(to: number, observed = true) {
      height = to;

      if (observed) {
        resize();
      }
    },
    /** Scrolls, as the user or the browser does. */
    scrollTo(top: number) {
      element.scrollTop = top;
      element.dispatchEvent(new Event('scroll'));
    },
  };
}

it('stays at the bottom as content grows, until the user scrolls up, and again once they scroll back', async () => {
  const {element, grow, scrollTo} = await setUp();

  grow(600);
  expect(element.scrollTop).toBe(500);

  // The scroll that pinning set off can arrive after more has rendered.
  grow(700, false);
  scrollTo(500);
  grow(700);
  expect(element.scrollTop).toBe(600);

  // The browser keeping what's in view in place scrolls down a little.
  grow(750, false);
  scrollTo(620);
  grow(750);
  expect(element.scrollTop).toBe(650);

  scrollTo(200);
  grow(800);
  expect(element.scrollTop).toBe(200);

  scrollTo(700);
  grow(900);
  expect(element.scrollTop).toBe(800);
});

it('starts unpinned when asked to, until pinned', async () => {
  const {pinned, element, grow} = await setUp({pinned: false});

  grow(600);
  expect(element.scrollTop).toBe(0);

  pinned.pin();
  pinned.stick();
  expect(element.scrollTop).toBe(500);
});

it('pins again after an input outside it scrolls it, if it was pinned', async () => {
  const {pinned, element, grow, scrollTo} = await setUp();
  grow(600);

  // Firefox scrolls it when the input's text changes.
  pinned.onBeforeInput();
  scrollTo(100);
  pinned.onInput();
  expect(element.scrollTop).toBe(500);

  // Scrolled up by the user first, it stays where Firefox leaves it.
  scrollTo(100);
  pinned.onBeforeInput();
  scrollTo(150);
  pinned.onInput();
  expect(element.scrollTop).toBe(150);
});
