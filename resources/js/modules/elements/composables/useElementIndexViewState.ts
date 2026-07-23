import {useLocalStorage} from '@/common/composables/useStorage';
import type {ViewState} from '@/modules/elements/types/view-state';

interface ElementIndexViewStateContext {
  elementType: string;
  context: string;
  viewState: Partial<ViewState>;
}

/**
 * The element index's persisted view configuration (view mode + per-source
 * column/sort overrides). Seeded from the server-provided view state and kept
 * in local storage, keyed per element type + context.
 *
 * Column visibility and sort are resolved per-source by their respective
 * composables (source `tableAttributes` / `defaultSort` → element-type
 * defaults), so only the user's explicit overrides live in `sources`.
 */
export function useElementIndexViewState(props: ElementIndexViewStateContext) {
  const initialViewState: ViewState = {
    inlineEditing: false,
    mode: 'table',
    nestedInputNamespace: null,
    showHeaderColumn: true,
    static: false,
    ...props.viewState,
  };

  return useLocalStorage<ViewState>(
    `elementindex.${props.elementType}.${props.context}`,
    initialViewState
  );
}
