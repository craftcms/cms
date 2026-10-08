import {computed, ref, type Ref, watch} from 'vue';
import type {SortingState, Updater} from '@tanstack/vue-table';
import type {
  IndexVisitor,
  IndexRestore,
} from '@/modules/elements/index/composables/useElementIndexVisits';
import {useServerSort} from '@/modules/admin-table/composables/useServerSort';
import type {SortItem} from '@/common/types';
import type {SortOption, ViewState} from '@/modules/elements/types/view-state';
import type {SourceItem} from '@/modules/elements/types/sources';

interface ElementIndexSortContext {
  sort: Array<SortItem>;
  source?: SourceItem | null;
  /** The sortable attributes; switching to one starts at its `defaultDir`. */
  sortOptions?: Array<SortOption>;
}

/** Keep valid, unique sort items in priority order. */
function normalizeSort(
  items: Array<SortItem> | Record<string, SortItem> | undefined
): Array<SortItem> {
  // `sort` round-trips through query params as an index-keyed object
  // (`{0: {...}}`), so persisted/rehydrated state can come back as an object
  // rather than an array. Coerce to an array before filtering.
  const list = Array.isArray(items) ? items : items ? Object.values(items) : [];
  const seen = new Set<string>();
  const normalized = list.filter((item) => {
    if (!item?.field || seen.has(item.field)) {
      return false;
    }

    seen.add(item.field);
    return true;
  });

  return normalized[0]?.field === 'score'
    ? normalized.slice(0, 1)
    : normalized.filter((item) => item.field !== 'score');
}

function sortItemsToQuery(items: Array<SortItem>) {
  return items.reduce<Record<string, {field: string; direction: string}>>(
    (acc, item, index) => {
      acc[index] = {field: item.field, direction: item.direction};
      return acc;
    },
    {}
  );
}

/**
 * Server-driven sorting for an element index. The URL is the source of truth:
 * column-header and popover changes push a sort-only Inertia visit, the
 * server-confirmed sort is mirrored back into the table state and local
 * storage, and a persisted sort is restored into the URL on load.
 */
export function useElementIndexSort(
  props: ElementIndexSortContext,
  viewState: Ref<ViewState>,
  visitor: IndexVisitor
) {
  // The user's sort is persisted per source, so each source falls back to its
  // own `defaultSort` (resolved server-side) until the user sorts it.
  const sourceKey = () => props.source?.key ?? '*';

  const persistedSort = () => viewState.value.sources?.[sourceKey()]?.sort;
  const confirmedSort = ref(normalizeSort(props.sort ?? persistedSort()));

  function setPersistedSort(sort: Array<SortItem>): void {
    const sources = {...viewState.value.sources};
    sources[sourceKey()] = {...sources[sourceKey()], sort};
    viewState.value.sources = sources;
  }

  const {
    sortingState,
    sortingConfig: serverSortingConfig,
    onSortingChange: changeServerSort,
  } = useServerSort({
    initialState: confirmedSort.value.slice(0, 1),
    // A non-page index keeps its query in the visitor, not in the URL.
    currentQuery: () => visitor.currentQuery(),
    onChange: ({query}) => {
      visitor.visit(query, {only: ['data', 'sort', 'pagination']});
    },
  });

  // Whenever the server confirms a sort, mirror it into the table state and local
  // storage.
  watch(
    () => props.sort,
    (sort) => {
      const next = normalizeSort(sort);
      confirmedSort.value = next;
      sortingState.value = next.slice(0, 1).map((item) => ({
        id: item.field,
        desc: item.direction === 'desc',
      }));
      if (next[0]?.field !== 'score') {
        setPersistedSort(next);
      }
    }
  );

  function onSortingChange(updater: Updater<SortingState>): void {
    const active =
      updater instanceof Function ? updater(sortingState.value) : updater;
    const requestedPrimary = active[0];

    if (!requestedPrimary) {
      return;
    }

    if (requestedPrimary.id === 'score') {
      changeServerSort([{id: 'score', desc: true}]);
      return;
    }

    const primary =
      requestedPrimary.id === 'sortOrder'
        ? {...requestedPrimary, desc: false}
        : requestedPrimary;

    const historySource =
      confirmedSort.value[0]?.field === 'score'
        ? normalizeSort(persistedSort())
        : confirmedSort.value;
    const history = historySource.filter(
      (item) => item.field !== primary.id && item.field !== 'score'
    );

    changeServerSort([
      primary,
      ...history.map((item) => ({
        id: item.field,
        desc: item.direction === 'desc',
      })),
    ]);
    confirmedSort.value = [
      {
        field: primary.id,
        direction: primary.desc ? 'desc' : 'asc',
      },
      ...history,
    ];
  }

  // On load, if the URL doesn't specify a sort but we have one persisted from a
  // previous visit, restore it. The page folds this into one mount-time restore
  // visit alongside the view-mode/column restores (see `useElementIndex`),
  // so they can't interrupt each other.
  function restore(): IndexRestore | null {
    const persisted = normalizeSort(persistedSort());
    const hasSortInQuery = visitor.currentQuery().sort !== undefined;

    if (hasSortInQuery || !persisted.length) {
      return null;
    }

    if (
      JSON.stringify(persisted) === JSON.stringify(normalizeSort(props.sort))
    ) {
      return null;
    }

    return {
      params: {sort: sortItemsToQuery(persisted)},
      only: ['data', 'sort', 'pagination'],
    };
  }

  // Two-way bindings for the single-column sort controls in the "View" popover.
  // They read from and write through the same sorting state as the column
  // headers, so the popover, the headers, the URL, and local storage stay in
  // sync.
  const sortField = computed<string>({
    get: () => sortingState.value[0]?.id ?? 'title',
    set: (field) => {
      // A newly chosen attribute starts at its own default direction (e.g.
      // dates sort newest-first); only same-field changes keep the current one.
      const option = props.sortOptions?.find((o) => o.value === field);
      onSortingChange([
        {
          id: field,
          desc:
            field === 'score' ||
            (option
              ? option.defaultDir === 'desc'
              : (sortingState.value[0]?.desc ?? false)),
        },
      ]);
    },
  });

  const sortDirection = computed<'asc' | 'desc'>({
    get: () => (sortingState.value[0]?.desc ? 'desc' : 'asc'),
    set: (direction) =>
      onSortingChange([{id: sortField.value, desc: direction === 'desc'}]),
  });

  const sortingConfig = {...serverSortingConfig, onSortingChange};

  return {
    sortingState,
    sortingConfig,
    onSortingChange,
    sortField,
    sortDirection,
    restore,
  };
}
