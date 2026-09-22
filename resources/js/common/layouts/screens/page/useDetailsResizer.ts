import {computed, toValue, type MaybeRefOrGetter, type Ref} from 'vue';
import {useWindowSize} from '@vueuse/core';
import {
  useResizable,
  type UseResizableReturn,
} from '@/common/composables/useResizable';

const DETAILS_MIN_WIDTH = 280;
const DETAILS_MAX_WIDTH = 550;
/** Hard ceiling on the column, whatever the layout around it allows. */
const DETAILS_MAX_VIEWPORT_SHARE = 0.8;
/** Share of the layout the column may take while it's covering the content. */
const DETAILS_MAX_OVERLAID_SHARE = 0.8;

interface DetailsResizerOptions {
  /** The details column being resized. */
  column: MaybeRefOrGetter<HTMLElement | null | undefined>;
  /** The measured width of the content layout the column sits in. */
  layoutWidth: Ref<number>;
  /** Whether the secondary nav is taking a share of the layout too. */
  hasSidebar: MaybeRefOrGetter<boolean>;
  /** Whether the column is covering the content rather than sharing the width. */
  overlaid: MaybeRefOrGetter<boolean>;
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
  overlaid,
}: DetailsResizerOptions): UseResizableReturn {
  const {width: viewportWidth} = useWindowSize();

  // Ceiling so a wide drag, or a width restored at a narrower viewport, can't
  // squeeze the content off the page. Keyed off the layout rather than its
  // columns, so it holds still mid-drag.
  const maxWidth = computed(() => {
    const viewportCap = Math.round(
      viewportWidth.value * DETAILS_MAX_VIEWPORT_SHARE
    );

    if (!layoutWidth.value) {
      return Math.min(DETAILS_MAX_WIDTH, viewportCap);
    }

    // Sharing the row, the column may have its half, less with a nav beside
    // it; covering the content, there's no column to leave room for.
    const share = toValue(overlaid)
      ? layoutWidth.value * DETAILS_MAX_OVERLAID_SHARE
      : layoutWidth.value * (toValue(hasSidebar) ? 0.4 : 0.5);

    const ceiling = toValue(overlaid)
      ? Math.round(share)
      : Math.min(DETAILS_MAX_WIDTH, Math.round(share));

    return Math.min(Math.max(DETAILS_MIN_WIDTH, ceiling), viewportCap);
  });

  // The floor gives way on a viewport too narrow for both, rather than
  // reporting a range the handle can't honour.
  const minWidth = computed(() => Math.min(DETAILS_MIN_WIDTH, maxWidth.value));

  return useResizable({
    target: column,
    edge: 'inline-start',
    minWidth,
    maxWidth,
    cssVariable: '--cp-content-details-width',
    storageKey: 'AppLayout.detailsWidth',
  });
}
