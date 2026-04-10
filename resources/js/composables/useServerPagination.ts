import {ref} from 'vue';
import type {
  PaginationState,
  TableOptionsWithReactiveData,
  Updater,
} from '@tanstack/vue-table';
import type {PaginationData} from '@/types';

interface OnChangeArgs {
  state: PaginationState;
  query: Record<string, string | number>;
}

interface UseServerPaginationParams {
  initialState: PaginationData;
  onChange: (args: OnChangeArgs) => void;
}

export function useServerPagination({
  initialState,
  onChange,
}: UseServerPaginationParams) {
  const pageParam = Craft.pageTrigger ?? 'page';
  const paginationState = ref<PaginationState>({
    pageIndex: initialState.current_page ? initialState.current_page - 1 : 0,
    pageSize: initialState.per_page,
  });

  function getNextPaginationParams(updater: Updater<PaginationState>) {
    const next =
      typeof updater === 'function' ? updater(paginationState.value) : updater;

    const currentQuery = new URLSearchParams(window.location.search);

    return {
      ...Object.fromEntries(currentQuery),
      [pageParam]: next.pageIndex + 1,
      per_page: next.pageSize,
    };
  }

  function onPaginationChange(updater: Updater<PaginationState>) {
    const query = getNextPaginationParams(updater);
    onChange({state: paginationState.value, query});
  }

  const paginationConfig = {
    manualPagination: true,
    rowCount: initialState.total,
    onPaginationChange,
  };

  return {
    paginationState,
    paginationConfig,
    onPaginationChange,
    getNextPaginationParams,
  };
}
