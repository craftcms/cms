import {
  computed,
  onScopeDispose,
  ref,
  shallowRef,
  toValue,
  watch,
  type ComputedRef,
  type MaybeRefOrGetter,
  type Ref,
} from 'vue';
import {useElementSize, useEventListener} from '@vueuse/core';
import {BaseDrag} from '@craftcms/garnish';
import type {ResizeHandleControls} from '@/common/composables/resizeHandle';

/**
 * Two-axis resizing for a centered box — a modal — from a corner handle.
 *
 * The sibling {@link import('./useResizable').useResizable} resizes a layout
 * column: one axis, anchored to an edge, persisted, reported to assistive tech
 * as a splitter. This is the other shape. A centered box grows from both edges
 * at once, has no meaningful "side", and is sized against whatever cap CSS
 * already puts on it rather than a track width.
 *
 * It also holds a height floor, because the two are the same question — how
 * big is this box — and answer to the same cap.
 */

export interface UseResizableBoxOptions {
  /** The element being resized. */
  target: MaybeRefOrGetter<HTMLElement | null | undefined>;
  /** Whether the box is on screen. The floor resets when it isn't. */
  active?: MaybeRefOrGetter<boolean>;
  /**
   * Set when the consumer fixes the height itself. The floor stands down: it
   * only means anything while the height is content-driven.
   */
  fixedHeight?: MaybeRefOrGetter<boolean>;
  /** Smallest size a drag can leave it at, in px. Default `200`. */
  minSize?: number;
  /** Arrow-key increment, in px. Default `16`. */
  step?: number;
  /** Shift + arrow-key increment, in px. Default `64`. */
  largeStep?: number;
}

export interface UseResizableBoxReturn extends ResizeHandleControls {
  /** Dragged width in px, or `null` while the width is CSS-driven. */
  width: Ref<number | null>;
  /** Dragged height in px, or `null` while the height is CSS-driven. */
  height: Ref<number | null>;
  /** Tallest the box has been while active, in px. Only ever rises. */
  floor: Ref<number | null>;
  /** The CSS the values above imply, or `{}` when the size is CSS-driven. */
  style: ComputedRef<Record<string, string>>;
}

export function useResizableBox({
  target,
  active,
  fixedHeight,
  minSize = 200,
  step = 16,
  largeStep = 64,
}: UseResizableBoxOptions): UseResizableBoxReturn {
  const width = ref<number | null>(null);
  const height = ref<number | null>(null);
  const floor = ref<number | null>(null);
  const handle = shallowRef<HTMLElement | null>(null);

  function el(): HTMLElement | null {
    return toValue(target) ?? null;
  }

  /**
   * The largest the box may be. Taken from the CSS rather than a hard-coded
   * gutter: max-width/max-height compute to px even when authored as calc(),
   * which the custom properties they're built from do not.
   */
  function bounds(node: HTMLElement): {width: number; height: number} {
    const styles = getComputedStyle(node);

    return {
      width: parseFloat(styles.maxWidth) || window.innerWidth,
      height: parseFloat(styles.maxHeight) || window.innerHeight,
    };
  }

  function measured(): {width: number; height: number} {
    const rect = el()?.getBoundingClientRect();

    return {
      width: Math.round(rect?.width ?? 0),
      height: Math.round(rect?.height ?? 0),
    };
  }

  function resize(nextWidth: number, nextHeight: number): void {
    const node = el();
    if (!node) return;

    const max = bounds(node);
    width.value = Math.round(Math.min(Math.max(nextWidth, minSize), max.width));
    height.value = Math.round(
      Math.min(Math.max(nextHeight, minSize), max.height)
    );
  }

  function reset(): void {
    width.value = null;
    height.value = null;
  }

  const style = computed<Record<string, string>>(() => {
    const style: Record<string, string> = {};

    if (width.value !== null) {
      style.width = `${width.value}px`;
    }

    if (height.value !== null) {
      style.height = `${height.value}px`;
    }

    // A floor under a fixed height is either redundant or, since min-height
    // beats max-height, actively wrong.
    if (
      floor.value !== null &&
      height.value === null &&
      !toValue(fixedHeight ?? false)
    ) {
      style.minHeight = `${floor.value}px`;
    }

    return style;
  });

  // --- Height floor ---------------------------------------------------------
  //
  // A box whose content shrinks would otherwise collapse under whoever is
  // reading it. Remember the tallest it has been and refuse to go below that.

  // Border-box, so the floor matches the rendered box the cap is measured
  // against — a content-box floor sits a border short and lets it creep down.
  const {height: renderedHeight} = useElementSize(target, undefined, {
    box: 'border-box',
  });

  watch(renderedHeight, (next) => {
    if (active !== undefined && !toValue(active)) return;
    if (!next) return;

    raiseFloor(Math.round(next));
  });

  if (active !== undefined) {
    watch(
      () => toValue(active),
      (isActive) => {
        // Reopening sizes itself to the new content rather than inheriting the
        // last session's floor.
        if (!isActive) floor.value = null;
      }
    );
  }

  function raiseFloor(next: number): void {
    const node = el();
    // min-height beats max-height, so the floor has to respect the cap itself
    // or a shrinking viewport would leave the box taller than the screen.
    const capped = Math.min(next, node ? bounds(node).height : Infinity);

    if (floor.value === null || capped > floor.value) {
      floor.value = capped;
    }
  }

  // --- Keyboard -------------------------------------------------------------

  function nudge(deltaX: number, deltaY: number): void {
    // Build on the last size we asked for, not the rendered one: key repeats
    // outrun Vue's DOM updates, so measuring every time would have them all
    // read the same stale box and overwrite each other.
    const size = measured();
    const node = el();
    const rtl = node ? getComputedStyle(node).direction === 'rtl' : false;

    resize(
      (width.value ?? size.width) + (rtl ? -deltaX : deltaX),
      (height.value ?? size.height) + deltaY
    );
  }

  function onKeydown(ev: KeyboardEvent): void {
    const increment = ev.shiftKey ? largeStep : step;

    switch (ev.key) {
      case 'ArrowLeft':
        nudge(-increment, 0);
        break;
      case 'ArrowRight':
        nudge(increment, 0);
        break;
      case 'ArrowUp':
        nudge(0, -increment);
        break;
      case 'ArrowDown':
        nudge(0, increment);
        break;
      case 'Enter':
        reset();
        break;
      default:
        return;
    }

    ev.preventDefault();
  }

  // --- Pointer dragging -----------------------------------------------------

  let dragger: BaseDrag | null = null;
  let startWidth = 0;
  let startHeight = 0;
  let startDistX = 0;
  let startDistY = 0;
  let sign = 1;

  function setHandle(node: HTMLElement | null): void {
    handle.value = node;
  }

  watch(handle, (node) => {
    dragger?.destroy();
    dragger = null;

    if (!node) return;

    dragger = new BaseDrag(node, {
      // The default selector list would swallow pointer-downs on the handle's
      // own children; the handle *is* the control here.
      ignoreHandleSelector: null,
      onBeforeDragStart: () => {
        // Sync, unlike onDragStart, so we measure what was on screen when the
        // drag threshold was crossed, and discount the distance already
        // travelled so the box doesn't jump by it.
        const size = measured();
        const box = el();
        startWidth = size.width;
        startHeight = size.height;
        startDistX = dragger?.mouseDistX ?? 0;
        startDistY = dragger?.mouseDistY ?? 0;
        sign = box && getComputedStyle(box).direction === 'rtl' ? -1 : 1;
      },
      onDrag: () => {
        const dx = ((dragger?.mouseDistX ?? 0) - startDistX) * sign;
        const dy = (dragger?.mouseDistY ?? 0) - startDistY;

        // Centered, so each edge moves by the pointer delta.
        resize(startWidth + dx * 2, startHeight + dy * 2);
      },
    });
  });

  // A smaller viewport lowers the cap; re-clamp so dragging back responds at
  // once rather than after the overshoot unwinds.
  useEventListener(window, 'resize', () => {
    if (width.value !== null && height.value !== null) {
      resize(width.value, height.value);
    }

    const node = el();
    if (floor.value !== null && node) {
      floor.value = Math.min(floor.value, bounds(node).height);
    }
  });

  onScopeDispose(() => {
    dragger?.destroy();
    dragger = null;
  });

  return {width, height, floor, style, setHandle, onKeydown, reset};
}
