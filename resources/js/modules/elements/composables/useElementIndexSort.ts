import {computed, type Ref, watch} from 'vue';
import {
  createIndexVisitor,
  type IndexVisitor,
  type ElementIndexRoute,
  type IndexRestore,
} from '@/modules/elements/composables/useElementIndexVisits';
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

interface UseElementIndexSortOptions {
  /** The index route to visit when the sort changes. */
  route: ElementIndexRoute;
  /** Called with the server-confirmed sort, e.g. to keep a filter form in sync. */
  onSortChange?: (sort: Array<SortItem>) => void;
  /** Supplied by indexes that aren't a page — see {@link createIndexVisitor}. */
  visitor?: IndexVisitor;
}

/**
 * The index supports a single sort, stored as an array (so multi-column sort
 * can be enabled later). Keep only valid items (those with a field) and clamp
 * to the first — this also self-heals any extra/garbage entries that may have
 * accumulated in persisted state.
 */
function normalizeSort(
  items: Array<SortItem> | Record<string, SortItem> | undefined
): Array<SortItem> {
  // `sort` round-trips through query params as an index-keyed object
  // (`{0: {...}}`), so persisted/rehydrated state can come back as an object
  // rather than an array. Coerce to an array before filtering.
  const list = Array.isArray(items) ? items : items ? Object.values(items) : [];
  return list.filter((item) => !!item?.field).slice(0, 1);
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
  options: UseElementIndexSortOptions
) {
  const visitor = options.visitor ?? createIndexVisitor(options.route);

  // The user's sort is persisted per source, so each source falls back to its
  // own `defaultSort` (resolved server-side) until the user sorts it.
  const sourceKey = () => props.source?.key ?? '*';

  const persistedSort = () => viewState.value.sources?.[sourceKey()]?.sort;

  function setPersistedSort(sort: Array<SortItem>): void {
    const sources = {...viewState.value.sources};
    sources[sourceKey()] = {...sources[sourceKey()], sort};
    viewState.value.sources = sources;
  }

  const {sortingState, sortingConfig, onSortingChange} = useServerSort({
    initialState: normalizeSort(props.sort ?? persistedSort()),
    onChange: ({query}) => {
      visitor.visit(query, {only: ['data', 'sort', 'pagination']});
    },
  });

  // Whenever the server confirms a sort, mirror it into the table state, local
  // storage, and (via callback) anything else that needs it.
  watch(
    () => props.sort,
    (sort) => {
      const next = normalizeSort(sort);
      sortingState.value = next.map((item) => ({
        id: item.field,
        desc: item.direction === 'desc',
      }));
      setPersistedSort(next);
      options.onSortChange?.(next);
    }
  );

  // On load, if the URL doesn't specify a sort but we have one persisted from a
  // previous visit, restore it. The page folds this into one mount-time restore
  // visit alongside the view-mode/column restores (see `useElementIndexPage`),
  // so they can't interrupt each other.
  function restore(): IndexRestore | null {
    const params = new URLSearchParams(window.location.search);
    const persisted = normalizeSort(persistedSort());

    // The sort is serialized as `sort[0][field]`, `sort[0][direction]`, … so we
    // can't look for a literal `sort` key — check for any bracketed sort param.
    const hasSortInUrl = [...params.keys()].some(
      (key) => key === 'sort' || key.startsWith('sort[')
    );

    if (hasSortInUrl || !persisted.length) {
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
          desc: option
            ? option.defaultDir === 'desc'
            : (sortingState.value[0]?.desc ?? false),
        },
      ]);
    },
  });

  const sortDirection = computed<'asc' | 'desc'>({
    get: () => (sortingState.value[0]?.desc ? 'desc' : 'asc'),
    set: (direction) =>
      onSortingChange([{id: sortField.value, desc: direction === 'desc'}]),
  });

  return {
    sortingState,
    sortingConfig,
    onSortingChange,
    sortField,
    sortDirection,
    restore,
  };
}
