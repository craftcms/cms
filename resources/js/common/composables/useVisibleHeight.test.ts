import {effectScope, nextTick, shallowRef} from 'vue';
import {afterEach, describe, expect, it, vi} from 'vite-plus/test';
import {useVisibleHeight} from './useVisibleHeight';

const scopes: ReturnType<typeof effectScope>[] = [];

// happy-dom lays nothing out, so a scroll is simulated by moving the element's box.
function measure(box: {top: number; height: number}) {
  const element = document.createElement('header');
  document.body.append(element);
  vi.spyOn(element, 'getBoundingClientRect').mockImplementation(
    () =>
      ({
        top: box.top,
        bottom: box.top + box.height,
        height: box.height,
        left: 0,
        right: 0,
        width: 0,
        x: 0,
        y: box.top,
      }) as DOMRect
  );

  const scope = effectScope();
  scopes.push(scope);
  const visible = scope.run(() => useVisibleHeight(shallowRef(element)))!;

  async function scrollTo(top: number): Promise<void> {
    box.top = top;
    window.dispatchEvent(new Event('scroll'));
    await nextTick();
  }

  return {visible, scrollTo};
}

describe('useVisibleHeight', () => {
  afterEach(() => {
    scopes.splice(0).forEach((scope) => scope.stop());
    document.body.innerHTML = '';
    vi.restoreAllMocks();
  });

  it('reports the whole element while it’s fully in view', async () => {
    const {visible} = measure({top: 0, height: 42});
    await nextTick();

    expect(visible.value).toBe(42);
  });

  it('shrinks as the element scrolls off the top', async () => {
    const {visible, scrollTo} = measure({top: 0, height: 42});

    await scrollTo(-20);

    expect(visible.value).toBe(22);
  });

  it('bottoms out at zero once the element has scrolled away', async () => {
    const {visible, scrollTo} = measure({top: 0, height: 42});

    await scrollTo(-400);

    expect(visible.value).toBe(0);
  });

  it('comes back as the page scrolls up again', async () => {
    const {visible, scrollTo} = measure({top: 0, height: 42});

    await scrollTo(-400);
    await scrollTo(-10);

    expect(visible.value).toBe(32);
  });

  it('only counts what’s above the bottom of the viewport', async () => {
    vi.spyOn(window, 'innerHeight', 'get').mockReturnValue(500);
    const {visible, scrollTo} = measure({top: 0, height: 42});

    await scrollTo(480);
    window.dispatchEvent(new Event('resize'));
    await nextTick();

    expect(visible.value).toBe(20);
  });
});
