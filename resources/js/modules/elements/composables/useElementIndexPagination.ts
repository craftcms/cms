import {watch} from 'vue';
import {router} from '@inertiajs/vue3';
import {index} from '@/routes/craft/cp/content/index.js';
import {useServerPagination} from '@/modules/admin-table/composables/useServerPagination';
import type {PaginationData} from '@/common/types';

interface ElementIndexPaginationContext {
  page: string;
  sectionHandle?: string | number;
  pagination: PaginationData;
}

/**
 * Server-driven pagination for an element index. Page/size changes push a
 * `data` + `pagination` Inertia visit, and the server-confirmed pagination is
 * mirrored back into the table state.
 */
export function useElementIndexPagination(props: ElementIndexPaginationContext) {
  const {paginationState, paginationConfig} = useServerPagination({
    initialState: props.pagination,
    onChange: ({query}) => {
      router.visit(
        index(
          {
            page: props.page ?? '',
            sectionHandle: props.sectionHandle ?? undefined,
          },
          {query}
        ),
        {
          only: ['data', 'pagination'],
          preserveState: true,
          preserveScroll: true,
        }
      );
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
