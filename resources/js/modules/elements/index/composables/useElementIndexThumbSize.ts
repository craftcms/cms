import {t} from '@craftcms/ui';
import {computed, toValue, type MaybeRefOrGetter, type Ref} from 'vue';
import type {
  IndexQueryParams,
  IndexRestore,
  IndexVisitor,
} from '@/modules/elements/index/composables/useElementIndexVisits';
import type {
  SourceViewState,
  ThumbSize as ThumbSizeValue,
  ViewMode,
  ViewState,
} from '@/modules/elements/types/view-state';

export type ThumbSizeView = keyof NonNullable<SourceViewState['thumbSizes']>;

/** Thumbnail tiles come in every size; table rows stop at a card's. */
const sizesByView: Record<ThumbSizeView, ThumbSizeValue[]> = {
  table: ['sm', 'md', 'lg'],
  thumbs: ['sm', 'md', 'lg', 'xl', '2xl'],
};

/** Tiles default to a card's thumbnail size, as they've always been. */
const defaultSizes: Record<ThumbSizeView, ThumbSizeValue> = {
  table: 'sm',
  thumbs: 'lg',
};

export function viewFor(mode: ViewMode['mode']): ThumbSizeView | null {
  switch (mode) {
    case 'table':
    case 'structure':
      return 'table';
    case 'thumbs':
      return 'thumbs';
    default:
      return null;
  }
}

/** A thumb size's name, as the View menu shows it. */
export function thumbSizeLabel(size: ThumbSizeValue): string {
  return {
    sm: t('Small'),
    md: t('Medium'),
    lg: t('Large'),
    xl: t('XL'),
    '2xl': t('2XL'),
  }[size];
}

/**
 * The size of the index's thumbnails, chosen in the View menu: one size for
 * table and structure views, another for thumbnail tiles, kept per source as
 * sort and columns are. Their chips take a
 * size to suit. The server renders the thumbnails at their size, so changing
 * it updates the local view state and then quietly re-requests the rows with
 * a `thumbSize` param, the same way `useElementIndexViewMode` handles
 * `viewMode`. The source's own size for a view is left out of the URL.
 */
export function useElementIndexThumbSize(
  viewState: Ref<ViewState>,
  visitor: IndexVisitor,
  mode: () => ViewMode['mode'],
  sourceKey: () => string,
  /** The current source's sizes, where it saved its own. */
  sourceDefaults?: MaybeRefOrGetter<Partial<
    Record<ThumbSizeView, ThumbSizeValue>
  > | null>
) {
  function defaultFor(view: ThumbSizeView): ThumbSizeValue {
    return toValue(sourceDefaults)?.[view] ?? defaultSizes[view];
  }

  function storedFor(
    view: ThumbSizeView,
    source: string = sourceKey()
  ): ThumbSizeValue | undefined {
    return viewState.value.sources?.[source]?.thumbSizes?.[view];
  }

  // Another source's own sizes only arrive with its rows.
  function sizeFor(
    view: ThumbSizeView,
    source: string = sourceKey()
  ): ThumbSizeValue {
    return (
      storedFor(view, source) ??
      (source === sourceKey() ? defaultFor(view) : defaultSizes[view])
    );
  }

  /** Keeps a size other than the source's own, or forgets it. */
  function store(view: ThumbSizeView, size: ThumbSizeValue | null): void {
    const source = viewState.value.sources?.[sourceKey()];
    const {[view]: _previous, ...others} = source?.thumbSizes ?? {};

    viewState.value.sources = {
      ...viewState.value.sources,
      [sourceKey()]: {
        ...source,
        thumbSizes:
          size && size !== defaultFor(view)
            ? {...others, [view]: size}
            : others,
      },
    };
  }

  /** The `thumbSize` param for a mode's rows, or null for the source's own. */
  function param(
    forMode: ViewMode['mode'],
    source: string = sourceKey()
  ): ThumbSizeValue | null {
    const view = viewFor(forMode);
    const stored = view ? storedFor(view, source) : undefined;

    if (!view || !stored) return null;
    if (source === sourceKey() && stored === defaultFor(view)) return null;

    return stored;
  }

  /** The sizes the current view offers, or none for a view without thumbnails. */
  const options = computed<ThumbSizeValue[]>(() => {
    const view = viewFor(mode());

    return view ? sizesByView[view] : [];
  });

  const thumbSize = computed<ThumbSizeValue>({
    get: () => {
      const view = viewFor(mode());

      return view ? sizeFor(view) : 'sm';
    },
    set: (value) => {
      const view = viewFor(mode());

      if (!view || value === sizeFor(view)) {
        return;
      }

      store(view, value);
      // Silent, since the rows already on screen were resized in place (see
      // `resizeThumbs()`); this only brings thumbnails rendered for the size.
      visitor.merge({thumbSize: param(mode())}, {only: ['data'], silent: true});
    },
  });

  // The server has no access to the persisted view state, so the first
  // payload is rendered at the URL's `thumbSize`, or else the view's default.
  // A size named in the URL wins; otherwise a persisted size other than the
  // default re-requests the rows as part of the page's mount-time restore
  // visit. Runs after the view-mode restore, so it sizes the mode that visit
  // will render.
  function restore(): IndexRestore | null {
    const view = viewFor(mode());

    if (!view) {
      return null;
    }

    const requested = visitor.currentQuery().thumbSize;

    if (requested !== undefined) {
      // The server falls back to the source's size for one the view doesn't
      // offer.
      store(
        view,
        sizesByView[view].includes(requested as ThumbSizeValue)
          ? (requested as ThumbSizeValue)
          : null
      );

      return null;
    }

    const size = param(mode());

    return size ? {params: {thumbSize: size}, only: ['data']} : null;
  }

  /**
   * Params a visit to another view mode or source needs, so its rows come at
   * its size.
   */
  function modeParams(
    forMode: ViewMode['mode'],
    source: string = sourceKey()
  ): IndexQueryParams {
    return {thumbSize: param(forMode, source)};
  }

  /** Whether the current view's thumbnails are at its default size. */
  const isDefault = computed(() => {
    const view = viewFor(mode());

    return !view || sizeFor(view) === defaultFor(view);
  });

  /**
   * Returns the current view's thumbnails to its default size. Folded into one
   * visit with the other View menu settings (see `useElementIndex`).
   */
  function resetToDefault(): IndexRestore | null {
    const view = viewFor(mode());

    if (!view || isDefault.value) {
      return null;
    }

    store(view, null);

    return {params: {thumbSize: null}, only: ['data']};
  }

  /** Both views' sizes as they stand, as a source would save them. */
  const sizes = computed<Record<ThumbSizeView, ThumbSizeValue>>(() => ({
    table: sizeFor('table'),
    thumbs: sizeFor('thumbs'),
  }));

  /** Forgets a view's size once the source saves it as its own. */
  function forget(view: ThumbSizeView): void {
    store(view, null);
  }

  return {
    thumbSize,
    options,
    restore,
    modeParams,
    isDefault,
    resetToDefault,
    sizes,
    forget,
  };
}

type Chip = HTMLElement & {
  thumbSize: ThumbSizeValue | null;
  size: string | null;
  updateComplete?: Promise<unknown>;
};

type Thumbnail = HTMLElement & {width: number | null; height: number | null};

/**
 * Resizes the index's chips already on screen to a new thumbnail size, and
 * scales their thumbnails to fill it, until the rows rendered for that size
 * arrive.
 *
 * A thumbnail's `width` and `height` are the size the server rendered it at,
 * and it's never shown larger, so they're scaled by however much its box
 * grew or shrank. Measuring the box, rather than looking the sizes up, holds
 * for `sm`, whose size depends on the chip's appearance.
 */
export async function resizeThumbs(
  root: ParentNode,
  size: ThumbSizeValue
): Promise<void> {
  const chips = [...root.querySelectorAll<Chip>('craft-chip[thumb-size]')];
  const thumbnails = chips.flatMap((chip) =>
    [...chip.querySelectorAll<Thumbnail>('craft-thumbnail')]
      .filter((thumbnail) => thumbnail.width && thumbnail.height)
      .map((thumbnail) => ({thumbnail, before: boxWidth(thumbnail)}))
  );

  for (const chip of chips) {
    // Properties rather than attributes, since rendered server HTML sets
    // them that way. The server sets the size the thumbnail suited, which
    // would now be stale.
    chip.thumbSize = size;
    chip.size = null;
  }

  await Promise.all(chips.map((chip) => chip.updateComplete));

  for (const {thumbnail, before} of thumbnails) {
    const scale = boxWidth(thumbnail) / before;

    if (before && scale && scale !== 1) {
      thumbnail.width = Math.round(thumbnail.width! * scale);
      thumbnail.height = Math.round(thumbnail.height! * scale);
    }
  }
}

function boxWidth(thumbnail: Thumbnail): number {
  return (
    thumbnail.shadowRoot
      ?.querySelector('[part="thumbnail"]')
      ?.getBoundingClientRect().width ?? 0
  );
}
