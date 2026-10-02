import {computed, type Ref} from 'vue';
import type {
  IndexVisitor,
  IndexRestore,
} from '@/modules/elements/index/composables/useElementIndexVisits';
import type {ViewMode, ViewState} from '@/modules/elements/types/view-state';

/**
 * Server-driven view mode. Switching modes updates the local view state
 * immediately (so the toolbar + view react without waiting on the network),
 * then pushes a `viewMode` Inertia visit that reflects the change in the URL
 * and refreshes the server-rendered elements. `preserveState` keeps the
 * optimistic local state in place while the server responds.
 */
export function useElementIndexViewMode(
  viewState: Ref<ViewState>,
  visitor: IndexVisitor
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
        {viewMode: value},
        {only: ['data', 'pagination', 'structure']}
      );
    },
  });

  // On a fresh full-page load the server renders for the default `table` mode
  // (it has no access to the persisted view state), so if local storage restored
  // a non-table mode, re-request the server-rendered elements for it. The page
  // folds this into one mount-time restore visit alongside the sort/column
  // restores (see `useElementIndex`), so they can't interrupt each other.
  function restore(): IndexRestore | null {
    const persisted = viewState.value.mode;

    if (
      visitor.currentQuery().viewMode !== undefined ||
      persisted === 'table'
    ) {
      return null;
    }

    return {
      params: {viewMode: persisted},
      only: ['data', 'pagination', 'structure'],
    };
  }

  return {mode, restore};
}
