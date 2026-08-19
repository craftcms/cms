import {watch} from 'vue';
import {
  createIndexVisitor,
  type IndexVisitor,
  type ElementIndexRoute,
} from '@/modules/elements/composables/useElementIndexVisits';
import {useServerPagination} from '@/modules/admin-table/composables/useServerPagination';
import type {PaginationData} from '@/common/types';

interface ElementIndexPaginationContext {
  pagination: PaginationData;
}

/**
 * Server-driven pagination for an element index. Page/size changes push a
 * `data` + `pagination` Inertia visit, and the server-confirmed pagination is
 * mirrored back into the table state.
 */
export function useElementIndexPagination(
  props: ElementIndexPaginationContext,
  route: ElementIndexRoute,
  /** Supplied by indexes that aren't a page — see {@link createIndexVisitor}. */
  indexVisitor?: IndexVisitor
) {
  const visitor = indexVisitor ?? createIndexVisitor(route);

  const {paginationState, paginationConfig} = useServerPagination({
    initialState: props.pagination,
    onChange: ({query}) => {
      visitor.visit(query, {only: ['data', 'pagination']});
    },
  });

  // Keep the table's pagination state in sync with the server-confirmed page
  // (also covers page resets triggered by sorting or filtering).
  watch(
    () => props.pagination,
    (pagination) => {
      paginationState.value = {
        pageIndex: pagination.current_page ? pagination.current_page - 1 : 0,
        pageSize: pagination.per_page,
      };
    }
  );

  return {paginationState, paginationConfig};
}
