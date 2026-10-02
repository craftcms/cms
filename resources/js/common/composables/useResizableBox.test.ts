import {effectScope, ref} from 'vue';
import {describe, expect, it} from 'vite-plus/test';
import {
  useResizableBox,
  type UseResizableBoxOptions,
  type UseResizableBoxReturn,
} from './useResizableBox';

/**
 * The style is a pure projection of `width`/`height`/`floor`, so the tests
 * set those directly rather than going through a drag — happy-dom reports every
 * box as 0×0, which the floor watcher ignores by design.
 */
function box(
  options: Partial<UseResizableBoxOptions> = {}
): UseResizableBoxReturn {
  const scope = effectScope();

  return scope.run(() =>
    useResizableBox({target: ref(null), ...options})
  ) as UseResizableBoxReturn;
}

describe('useResizableBox style', () => {
  it('leaves the size to CSS until something sets one', () => {
    expect(box().style.value).toEqual({});
  });

  it('holds the box open at the floor while the height is content-driven', () => {
    const {floor, style} = box();
    floor.value = 400;

    expect(style.value).toEqual({minHeight: '400px'});
  });

  it('drops the floor once a drag fixes the height', () => {
    const {width, height, floor, style} = box();
    floor.value = 400;
    width.value = 800;
    // Deliberately below the floor: a drag may shrink the box past it, and
    // min-height beats max-height, so a surviving floor would fight the drag.
    height.value = 300;

    expect(style.value).toEqual({width: '800px', height: '300px'});
  });

  it('stands down when the consumer fixes the height itself', () => {
    const fixed = ref(false);
    const {floor, style} = box({fixedHeight: fixed});
    floor.value = 400;

    expect(style.value).toHaveProperty('minHeight', '400px');

    fixed.value = true;
    expect(style.value).toEqual({});
  });

  it('still reports a dragged width when the height is fixed', () => {
    const {width, floor, style} = box({fixedHeight: () => true});
    floor.value = 400;
    width.value = 800;

    expect(style.value).toEqual({width: '800px'});
  });
});
