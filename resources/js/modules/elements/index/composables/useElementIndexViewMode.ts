import {computed, type Ref} from 'vue';
import type {
  IndexQueryParams,
  IndexVisitor,
  IndexRestore,
} from '@/modules/elements/index/composables/useElementIndexVisits';
import type {ViewMode, ViewState} from '@/modules/elements/types/view-state';

/**
 * The props a mode change re-renders. `viewState` comes along so its `mode`
 * keeps naming the mode the rows were rendered for: Inertia stores the page in
 * history, and going back to it calls `restore()` again, which trusts that mode.
 */
const MODE_PROPS = ['data', 'pagination', 'structure', 'viewState'];

/**
 * Server-driven view mode. Switching modes updates the local view state
 * immediately (so the toolbar + view react without waiting on the network),
 * then pushes a `viewMode` Inertia visit that reflects the change in the URL
 * and refreshes the server-rendered elements. `preserveState` keeps the
 * optimistic local state in place while the server responds.
 */
export function useElementIndexViewMode(
  viewState: Ref<ViewState>,
  visitor: IndexVisitor,
  /** The mode the current payload was rendered for, if it says. */
  renderedMode: () => ViewMode['mode'] | undefined = () => undefined,
  /** The modes the index can show, if it's limited to some. */
  availableModes: () => ViewMode['mode'][] | undefined = () => undefined,
  /** Other params a mode's rows depend on, such as their thumbnail size. */
  modeParams: (mode: ViewMode['mode']) => IndexQueryParams = () => ({})
) {
  const mode = computed<ViewMode['mode']>({
    get: () => viewState.value.mode,
    set: (value) => {
      if (value === viewState.value.mode) {
        return;
      }

      // Update locally first so the view + active button switch immediately.
      viewState.value.mode = value;

      // Reflect the change in the URL and refresh the server-rendered bits.
      // `structure` comes along because the server only fills it in while the
      // index is ordered by structure — without it, switching into structure
      // mode leaves the payload's structure null until a full page load, and
      // the rows render with no reordering affordances.
      visitor.merge(
        {viewMode: value, ...modeParams(value)},
        {only: MODE_PROPS}
      );
    },
  });

  // The server has no access to the persisted view state, so the first payload
  // is rendered for the URL's `viewMode`, or else the source's default mode.
  // Rows rendered for one mode don't draw in another — table rows have no card
  // HTML — so the two have to agree. The page folds this into one mount-time
  // restore visit alongside the sort/column restores (see `useElementIndex`),
  // so they can't interrupt each other.
  function restore(): IndexRestore | null {
    const persisted = viewState.value.mode;
    const rendered = renderedMode() ?? 'table';

    // A mode named in the URL wins over the persisted one.
    if (visitor.currentQuery().viewMode !== undefined) {
      if (rendered !== persisted) {
        viewState.value.mode = rendered;
      }

      return null;
    }

    // Otherwise, re-request the rows for the persisted mode — unless it's one
    // the index can't show, in which case the view falls back to what was
    // rendered anyway.
    if (
      persisted === rendered ||
      availableModes()?.includes(persisted) === false
    ) {
      return null;
    }

    return {
      params: {viewMode: persisted},
      only: MODE_PROPS,
    };
  }

  return {mode, restore};
}
