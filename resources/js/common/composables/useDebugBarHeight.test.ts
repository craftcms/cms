import {effectScope, nextTick} from 'vue';
import {afterEach, describe, expect, it, vi} from 'vite-plus/test';
import {useDebugBarHeight} from './useDebugBarHeight';

const scopes: ReturnType<typeof effectScope>[] = [];

// happy-dom lays nothing out, so the bar is given a box pinned to the viewport's bottom edge.
function debugBar(box: {height: number}): HTMLElement {
  const element = document.createElement('div');
  element.className = 'phpdebugbar phpdebugbar-minimized';
  vi.spyOn(element, 'getBoundingClientRect').mockImplementation(
    () =>
      ({
        top: window.innerHeight - box.height,
        bottom: window.innerHeight,
        height: box.height,
        left: 0,
        right: 0,
        width: 0,
        x: 0,
        y: window.innerHeight - box.height,
      }) as DOMRect
  );

  return element;
}

function measure() {
  const scope = effectScope();
  scopes.push(scope);

  return scope.run(() => useDebugBarHeight())!;
}

/** MutationObserver callbacks run as microtasks, then Vue's scheduler flushes. */
async function settle(): Promise<void> {
  await Promise.resolve();
  await nextTick();
}

describe('useDebugBarHeight', () => {
  afterEach(() => {
    scopes.splice(0).forEach((scope) => scope.stop());
    document.body.innerHTML = '';
    vi.restoreAllMocks();
  });

  it('is null without a debug bar', () => {
    expect(measure().value).toBeNull();
  });

  it('measures a bar that’s already on the page', async () => {
    document.body.append(debugBar({height: 33}));

    const height = measure();
    await settle();

    expect(height.value).toBe(33);
  });

  it('picks up a bar its script appends later', async () => {
    const height = measure();

    document.body.append(debugBar({height: 33}));
    await settle();

    expect(height.value).toBe(33);
  });

  it('follows the bar as it’s opened', async () => {
    const box = {height: 33};
    document.body.append(debugBar(box));
    const height = measure();
    await settle();

    box.height = 300;
    window.dispatchEvent(new Event('resize'));
    await settle();

    expect(height.value).toBe(300);
  });

  it('goes back to null when the bar is removed', async () => {
    const bar = debugBar({height: 33});
    document.body.append(bar);
    const height = measure();
    await settle();

    bar.remove();
    await settle();

    expect(height.value).toBeNull();
  });
});
