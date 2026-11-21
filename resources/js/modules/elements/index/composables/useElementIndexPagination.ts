import {watch, type Ref} from 'vue';
import type {
  IndexRestore,
  IndexVisitor,
} from '@/modules/elements/index/composables/useElementIndexVisits';
import {useServerPagination} from '@/common/table/useServerPagination';
import type {PaginationData} from '@/common/types';
import type {ViewState} from '@/modules/elements/types/view-state';

interface ElementIndexPaginationContext {
  pagination?: PaginationData;
}

/** Stands in until a page load's deferred pagination arrives. */
const pendingPagination: PaginationData = {
  total: 0,
  per_page: 50,
  current_page: 1,
  last_page: 1,
  next_page_url: null,
  prev_page_url: null,
  from: null,
  to: null,
};

/**
 * Server-driven pagination for an element index. Page/size changes push a
 * `data` + `pagination` Inertia visit, and the server-confirmed pagination is
 * mirrored back into the table state. The page size is kept per source, as
 * sort and columns are.
 */
export function useElementIndexPagination(
  props: ElementIndexPaginationContext,
  viewState: Ref<ViewState>,
  visitor: IndexVisitor,
  sourceKey: () => string
) {
  const persistedPageSize = () =>
    viewState.value.sources?.[sourceKey()]?.pageSize;

  const {paginationState, paginationConfig} = useServerPagination({
    initialState: props.pagination ?? pendingPagination,
    // A non-page index keeps its query in the visitor, not in the URL.
    currentQuery: () => visitor.currentQuery(),
    onChange: ({state, query}) => {
      if (query.per_page !== state.pageSize) {
        viewState.value.sources = {
          ...viewState.value.sources,
          [sourceKey()]: {
            ...viewState.value.sources?.[sourceKey()],
            pageSize: Number(query.per_page),
          },
        };
      }

      visitor.visit(query, {only: ['data', 'pagination']});
    },
  });

  // Keep the table's pagination state in sync with the server-confirmed page
  // (also covers page resets triggered by sorting or filtering).
  watch(
    () => props.pagination,
    (pagination) => {
      if (!pagination) {
        return;
      }

      paginationState.value = {
        pageIndex: pagination.current_page ? pagination.current_page - 1 : 0,
        pageSize: pagination.per_page,
      };
    }
  );

  // The server has no access to the persisted view state, so the first
  // payload is paginated by the URL's `per_page`, or else the default. A size
  // named in the URL wins; otherwise a persisted one is requested as part of
  // the page's mount-time restore visit (see `useElementIndex`).
  function restore(): IndexRestore | null {
    const persisted = persistedPageSize();
    const rendered = props.pagination?.per_page ?? pendingPagination.per_page;

    if (
      persisted === undefined ||
      persisted === rendered ||
      visitor.currentQuery().per_page !== undefined
    ) {
      return null;
    }

    return {params: {per_page: persisted}, only: ['data', 'pagination']};
  }

  return {paginationState, paginationConfig, restore};
}
