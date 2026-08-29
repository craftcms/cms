import {computed, type Ref} from 'vue';
import {
  createIndexVisitor,
  type IndexVisitor,
  type ElementIndexRoute,
  type IndexRestore,
} from '@/modules/elements/composables/useElementIndexVisits';
import type {ViewMode, ViewState} from '@/modules/elements/types/view-state';

/**
 * Server-driven view mode. Switching modes updates the local view state
 * immediately (so the toolbar + view react without waiting on the network),
 * then pushes a `viewMode` Inertia visit that reflects the change in the URL
 * and refreshes the server-rendered elements. `preserveState` keeps the
 * optimistic local state in place while the server responds.
 */
export function useElementIndexViewMode(
  route: ElementIndexRoute,
  viewState: Ref<ViewState>,
  /** Supplied by indexes that aren't a page — see {@link createIndexVisitor}. */
  indexVisitor?: IndexVisitor
) {
  const visitor = indexVisitor ?? createIndexVisitor(route);

  const mode = computed<ViewMode['mode']>({
    get: () => viewState.value.mode,
    set: (value) => {
      if (value === viewState.value.mode) {
        return;
      }

      // Update locally first so the view + active button switch immediately.
      viewState.value.mode = value;

      // Reflect the change in the URL and refresh the server-rendered bits.
      visitor.merge({viewMode: value}, {only: ['data', 'pagination']});
    },
  });

  // On a fresh full-page load the server renders for the default `table` mode
  // (it has no access to the persisted view state), so if local storage restored
  // a non-table mode, re-request the server-rendered elements for it. The page
  // folds this into one mount-time restore visit alongside the sort/column
  // restores (see `useElementIndexPage`), so they can't interrupt each other.
  function restore(): IndexRestore | null {
    const params = new URLSearchParams(window.location.search);
    const persisted = viewState.value.mode;

    if (params.has('viewMode') || persisted === 'table') {
      return null;
    }

    return {params: {viewMode: persisted}, only: ['data', 'pagination']};
  }

  return {mode, restore};
}
