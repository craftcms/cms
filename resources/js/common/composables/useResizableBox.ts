import {
  computed,
  ref,
  toValue,
  watch,
  type ComputedRef,
  type MaybeRefOrGetter,
  type Ref,
} from 'vue';
import {useElementSize, useEventListener} from '@vueuse/core';
import type {ResizeGripsSize} from '@craftcms/ui';

/**
 * The size of a centered box — a modal — that `<craft-resize-grips>` resizes.
 *
 * The grips do the dragging, the keyboard and the clamping, and report each
 * size, which {@link UseResizableBoxReturn.applySize} takes. This holds that
 * size and the CSS it implies.
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
}

export interface UseResizableBoxReturn {
  /** Dragged width in px, or `null` while the width is CSS-driven. */
  width: Ref<number | null>;
  /** Dragged height in px, or `null` while the height is CSS-driven. */
  height: Ref<number | null>;
  /** Tallest the box has been while active, in px. Only ever rises. */
  floor: Ref<number | null>;
  /** The CSS the values above imply, or `{}` when the size is CSS-driven. */
  style: ComputedRef<Record<string, string>>;
  /** Takes a size the grips reported. Wire to `@craft-resize`'s `detail`. */
  applySize: (size: ResizeGripsSize) => void;
}

export function useResizableBox({
  target,
  active,
  fixedHeight,
}: UseResizableBoxOptions): UseResizableBoxReturn {
  const width = ref<number | null>(null);
  const height = ref<number | null>(null);
  const floor = ref<number | null>(null);

  function el(): HTMLElement | null {
    return toValue(target) ?? null;
  }

  /**
   * The tallest the box may be. Taken from the CSS rather than a hard-coded
   * gutter: max-height computes to px even when authored as calc(), which the
   * custom properties it's built from do not.
   */
  function maxHeight(node: HTMLElement): number {
    return parseFloat(getComputedStyle(node).maxHeight) || window.innerHeight;
  }

  function applySize(size: ResizeGripsSize): void {
    width.value = size.width;
    height.value = size.height;
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
    const capped = Math.min(next, node ? maxHeight(node) : Infinity);

    if (floor.value === null || capped > floor.value) {
      floor.value = capped;
    }
  }

  // A smaller viewport lowers the cap. The grips re-clamp a dragged size; the
  // floor is this composable's own.
  useEventListener(window, 'resize', () => {
    const node = el();
    if (floor.value !== null && node) {
      floor.value = Math.min(floor.value, maxHeight(node));
    }
  });

  return {width, height, floor, style, applySize};
}
