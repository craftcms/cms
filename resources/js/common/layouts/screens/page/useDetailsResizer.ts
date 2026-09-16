import {computed, toValue, type MaybeRefOrGetter, type Ref} from 'vue';
import {
  useResizable,
  type UseResizableReturn,
} from '@/common/composables/useResizable';

/** Resize bounds for the details column, in px — 12rem to 30rem. */
const DETAILS_MIN_WIDTH = 192;
const DETAILS_MAX_WIDTH = 480;

interface DetailsResizerOptions {
  /** The details column being resized. */
  column: MaybeRefOrGetter<HTMLElement | null | undefined>;
  /** The measured width of the content layout the column sits in. */
  layoutWidth: Ref<number>;
  /** Whether the secondary nav is taking a share of the layout too. */
  hasSidebar: MaybeRefOrGetter<boolean>;
}

/**
 * Drag-to-resize for the page's details column.
 *
 * The width lands on `--cp-content-details-width`, which the content layout's
 * grid reads, so the returned `style` belongs on that layout rather than on
 * the column. Leaving it unset keeps the stylesheet's responsive default. Not
 * `--details-width`: legacy `_cp.scss` already publishes one globally.
 */
export function useDetailsResizer({
  column,
  layoutWidth,
  hasSidebar,
}: DetailsResizerOptions): UseResizableReturn {
  // Ceiling so a wide drag, or a width restored at a narrower viewport, can't
  // squeeze the main column off the page. Mirrors the track's `min()` cap, and
  // keys off the layout rather than its columns so it holds still mid-drag.
  const maxWidth = computed(() => {
    if (!layoutWidth.value) {
      return DETAILS_MAX_WIDTH;
    }

    const share = layoutWidth.value * (toValue(hasSidebar) ? 0.4 : 0.5);

    return Math.max(
      DETAILS_MIN_WIDTH,
      Math.min(DETAILS_MAX_WIDTH, Math.round(share))
    );
  });

  return useResizable({
    target: column,
    edge: 'inline-start',
    minWidth: DETAILS_MIN_WIDTH,
    maxWidth,
    cssVariable: '--cp-content-details-width',
    storageKey: 'AppLayout.detailsWidth',
  });
}
