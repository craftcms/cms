import {toValue, watch, type MaybeRefOrGetter} from 'vue';
import {useResizeObserver} from '@vueuse/core';

/**
 * Eases a box from its old size to its new one when its content changes —
 * a modal whose spinner gives way to what it loaded, say — instead of letting
 * it jump.
 *
 * The animation drives `min-height` along with `height`, so a height floor
 * (see `useResizableBox`) that rises to the new size straight away can't
 * clamp it. Sizes the user sets by resizing aren't animated: call
 * {@link UseAnimatedBoxSizeReturn.settle} before applying one.
 */

export interface UseAnimatedBoxSizeOptions {
  /** The element whose size is animated. */
  target: MaybeRefOrGetter<HTMLElement | null | undefined>;
  /** Whether size changes animate. Changes made while off are taken as given. */
  enabled?: MaybeRefOrGetter<boolean>;
  /** Milliseconds each change takes. */
  duration?: number;
}

export interface UseAnimatedBoxSizeReturn {
  /** Stops any animation and takes the next size change as given. */
  settle: () => void;
}

interface Size {
  width: number;
  height: number;
}

export function useAnimatedBoxSize({
  target,
  enabled = true,
  duration = 200,
}: UseAnimatedBoxSizeOptions): UseAnimatedBoxSizeReturn {
  let last: Size | null = null;
  let animation: Animation | null = null;
  let skipNext = false;

  /** Layout size, which the enter transition's scale doesn't distort. */
  function measure(element: HTMLElement): Size {
    return {width: element.offsetWidth, height: element.offsetHeight};
  }

  function same(a: Size, b: Size): boolean {
    return a.width === b.width && a.height === b.height;
  }

  function animate(element: HTMLElement, from: Size, to: Size): void {
    const current = element.animate(
      [
        {
          width: `${from.width}px`,
          height: `${from.height}px`,
          minHeight: `${from.height}px`,
        },
        {
          width: `${to.width}px`,
          height: `${to.height}px`,
          minHeight: `${to.height}px`,
        },
      ],
      {duration, easing: 'ease-out'}
    );
    animation = current;

    current.finished.then(
      () => {
        if (animation !== current) return;
        animation = null;
        // Content that changed mid-animation was held at the animated size.
        const settled = measure(element);
        last = settled;
        if (!same(settled, to) && element.isConnected) {
          animate(element, to, settled);
        }
      },
      () => {
        if (animation === current) animation = null;
      }
    );
  }

  useResizeObserver(target, () => {
    const element = toValue(target);
    if (!element || animation) return;

    const next = measure(element);
    const previous = last;
    last = next;

    if (skipNext || !previous || !toValue(enabled)) {
      skipNext = false;
      return;
    }

    if (!same(previous, next)) {
      animate(element, previous, next);
    }
  });

  watch(
    () => toValue(target),
    () => {
      animation?.cancel();
      animation = null;
      last = null;
    }
  );

  function settle(): void {
    animation?.cancel();
    animation = null;
    skipNext = true;
  }

  return {settle};
}
