import {ref} from 'vue';
import type {IndexQueryParams} from '@/modules/elements/composables/useElementIndexVisits';
import type {PaginationState, Updater} from '@tanstack/vue-table';
import type {PaginationData} from '@/common/types';

interface OnChangeArgs {
  state: PaginationState;
  query: Record<string, string | number>;
}

interface UseServerPaginationParams {
  initialState: PaginationData;
  onChange: (args: OnChangeArgs) => void;
  /**
   * The query the new page is applied on top of.
   *
   * Defaults to the page's own URL, which is right for a table that *is* the
   * page. An index that isn't one — the element selector modal — has no URL to
   * read, and taking the host page's would both lose its own state (the chosen
   * source) and drag in params that aren't its.
   */
  currentQuery?: () => IndexQueryParams;
}

export function useServerPagination({
  initialState,
  onChange,
  currentQuery,
}: UseServerPaginationParams) {
  const pageParam = Craft.pageTrigger ?? 'page';
  const paginationState = ref<PaginationState>({
    pageIndex: initialState.current_page ? initialState.current_page - 1 : 0,
    pageSize: initialState.per_page,
  });

  function getNextPaginationParams(updater: Updater<PaginationState>) {
    const next =
      updater instanceof Function ? updater(paginationState.value) : updater;

    return {
      // Cast because the caller's query is its own shape — a non-page index
      // carries structured values (a `sort` object) a URLSearchParams never
      // could. This composable only passes it through.
      ...((currentQuery?.() ??
        Object.fromEntries(
          new URLSearchParams(window.location.search)
        )) as Record<string, string>),
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
