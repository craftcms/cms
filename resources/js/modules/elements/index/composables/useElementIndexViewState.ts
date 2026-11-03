import {useLocalStorage} from '@/common/composables/useStorage';
import type {ViewState} from '@/modules/elements/types/view-state';

interface ElementIndexViewStateContext {
  elementType: string;
  context: string;
  viewState: Partial<ViewState>;
}

/**
 * The element index's persisted view configuration (per-source view mode,
 * column, sort, and thumbnail size overrides). Seeded from the
 * server-provided view state and kept in local storage, keyed per element
 * type + context.
 *
 * Column visibility and sort are resolved per-source by their respective
 * composables (source `tableAttributes` / `defaultSort` → element-type
 * defaults), so only the user's explicit overrides live in `sources`.
 *
 * Changes made in other tabs aren't followed: the rows on screen were
 * rendered for this tab's view state, and table rows can't draw as cards (nor
 * columns or sort change) without a new request. They apply on the next load.
 */
export function useElementIndexViewState(
  props: ElementIndexViewStateContext,
  storageKey = `elementindex.${props.elementType}.${props.context}`
) {
  const initialViewState: ViewState = {
    nestedInputNamespace: null,
    showHeaderColumn: true,
    static: false,
    ...props.viewState,
  };

  return useLocalStorage<ViewState>(storageKey, initialViewState, {
    listenToStorageChanges: false,
  });
}
