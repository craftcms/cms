import {computed, type Ref} from 'vue';
import type {
  IndexQueryParams,
  IndexRestore,
  IndexVisitor,
} from '@/modules/elements/index/composables/useElementIndexVisits';
import type {
  ThumbSize as ThumbSizeValue,
  ViewMode,
  ViewState,
} from '@/modules/elements/types/view-state';

type ThumbSizeView = keyof NonNullable<ViewState['thumbSizes']>;

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

function viewFor(mode: ViewMode['mode']): ThumbSizeView | null {
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

/**
 * The size of the index's thumbnails, chosen in the View menu: one size for
 * table and structure views, another for thumbnail tiles. Their chips take a
 * size to suit. The server renders the thumbnails at their size, so changing
 * it updates the local view state and then re-requests the rows with a
 * `thumbSize` param, the same way
 * `useElementIndexViewMode` handles `viewMode`. A view's default size is left
 * out of the URL.
 */
export function useElementIndexThumbSize(
  viewState: Ref<ViewState>,
  visitor: IndexVisitor,
  mode: () => ViewMode['mode']
) {
  function sizeFor(view: ThumbSizeView): ThumbSizeValue {
    return viewState.value.thumbSizes?.[view] ?? defaultSizes[view];
  }

  function store(view: ThumbSizeView, size: ThumbSizeValue): void {
    viewState.value.thumbSizes = {...viewState.value.thumbSizes, [view]: size};
  }

  /** The `thumbSize` param for a mode's rows, or null for its default. */
  function param(forMode: ViewMode['mode']): ThumbSizeValue | null {
    const view = viewFor(forMode);

    if (!view || sizeFor(view) === defaultSizes[view]) {
      return null;
    }

    return sizeFor(view);
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
      visitor.merge({thumbSize: param(mode())}, {only: ['data']});
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
      // The server falls back to the default for a size the view doesn't
      // offer.
      store(
        view,
        sizesByView[view].includes(requested as ThumbSizeValue)
          ? (requested as ThumbSizeValue)
          : defaultSizes[view]
      );

      return null;
    }

    const size = param(mode());

    return size ? {params: {thumbSize: size}, only: ['data']} : null;
  }

  /** Params a visit to another view mode needs, so its rows come at its size. */
  function modeParams(forMode: ViewMode['mode']): IndexQueryParams {
    return {thumbSize: param(forMode)};
  }

  return {thumbSize, options, restore, modeParams};
}
