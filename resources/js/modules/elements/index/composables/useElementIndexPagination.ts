import {watch} from 'vue';
import type {IndexVisitor} from '@/modules/elements/index/composables/useElementIndexVisits';
import {useServerPagination} from '@/common/table/useServerPagination';
import type {PaginationData} from '@/common/types';

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
 * mirrored back into the table state.
 */
export function useElementIndexPagination(
  props: ElementIndexPaginationContext,
  visitor: IndexVisitor
) {
  const {paginationState, paginationConfig} = useServerPagination({
    initialState: props.pagination ?? pendingPagination,
    // A non-page index keeps its query in the visitor, not in the URL.
    currentQuery: () => visitor.currentQuery(),
    onChange: ({query}) => {
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

  return {paginationState, paginationConfig};
}
