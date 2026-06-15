import {useLocalStorage} from '@/common/composables/useStorage';
import type {SortItem} from '@/common/types';
import type {ViewState} from '@/modules/elements/types/view-state';

interface ElementIndexViewStateContext {
  elementType: string;
  context: string;
  sort: Array<SortItem>;
  viewState: Partial<ViewState>;
}

/**
 * The element index's persisted view configuration (mode, table columns,
 * column order, sort). Seeded from the server-provided view state and kept in
 * local storage, keyed per element type + context.
 */
export function useElementIndexViewState(props: ElementIndexViewStateContext) {
  const initialViewState: ViewState = {
    inlineEditing: false,
    mode: 'table',
    // Column visibility is resolved per-source in `useElementIndexColumns`
    // (source `tableAttributes` → element-type defaults), so it isn't seeded
    // here; only the user's explicit per-source overrides live in `columns`.
    nestedInputNamespace: null,
    showHeaderColumn: true,
    sort: props.sort ?? [{field: 'dateCreated', direction: 'desc'}],
    static: false,
    ...props.viewState,
  };

  return useLocalStorage<ViewState>(
    `elementindex.${props.elementType}.${props.context}`,
    initialViewState
  );
}
