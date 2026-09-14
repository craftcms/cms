import {ref} from 'vue';
import type {IndexQueryParams} from '@/modules/elements/composables/useElementIndexVisits';
import type {SortingState, Updater} from '@tanstack/vue-table';
import type {SortItem} from '@/common/types';

interface OnChangeArgs {
  state: SortingState;
  query: Record<
    string,
    string | number | Record<number, {field: string; direction: string}>
  >;
}

interface UseServerSortParams {
  initialState: Array<SortItem>;
  onChange: (args: OnChangeArgs) => void;
  /**
   * The query the new sort is applied on top of.
   *
   * Defaults to the page's own URL, which is right for a table that *is* the
   * page. An index that isn't one — the element selector modal — has no URL to
   * read, and taking the host page's would both lose its own state (the chosen
   * source) and drag in params that aren't its.
   */
  currentQuery?: () => IndexQueryParams;
}

export function useServerSort({
  initialState,
  onChange,
  currentQuery,
}: UseServerSortParams) {
  const pageParam = Craft.pageTrigger ?? 'page';
  const sortingState = ref<SortingState>(
    initialState
      ? initialState.map((sort) => ({
          id: sort.field,
          desc: sort.direction === 'desc',
        }))
      : []
  );

  function getNextSortParams(updater: Updater<SortingState>) {
    const next =
      updater instanceof Function ? updater(sortingState.value) : updater;

    // Convert array of objects to indexed object format
    const sortQueryParams = next.reduce<
      Record<number, {field: string; direction: string}>
    >((acc, sortCol, index) => {
      acc[index] = {
        field: sortCol.id,
        direction: sortCol.desc ? 'desc' : 'asc',
      };

      return acc;
    }, {});

    return {
      // Cast because the caller's query is its own shape — a non-page index
      // carries structured values (a `sort` object) a URLSearchParams never
      // could. This composable only passes it through.
      ...((currentQuery?.() ??
        Object.fromEntries(
          new URLSearchParams(window.location.search)
        )) as Record<string, string>),
      sort: sortQueryParams,
      [pageParam]: 1,
    };
  }

  function onSortingChange(updater: Updater<SortingState>) {
    const query = getNextSortParams(updater);
    onChange({query, state: sortingState.value});
  }

  const sortingConfig = {
    manualSorting: true,
    enableMultiSort: true,
    enableSortingRemoval: false,
    onSortingChange,
  };

  return {
    sortingState,
    sortingConfig,
    onSortingChange,
    getNextSortParams,
  };
}
