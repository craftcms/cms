import {ref} from 'vue';
import type {SortingState, Updater} from '@tanstack/vue-table';
import type {SortItem} from '@/types';

interface OnChangeArgs {
  state: SortingState;
  query: Record<string, any>;
}

interface UseServerSortParams {
  initialState: Array<SortItem>;
  onChange: (args: OnChangeArgs) => void;
}

export function useServerSort({initialState, onChange}: UseServerSortParams) {
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
      typeof updater === 'function' ? updater(sortingState.value) : updater;

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

    const currentQuery = new URLSearchParams(window.location.search);
    return {
      ...Object.fromEntries(currentQuery),
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
