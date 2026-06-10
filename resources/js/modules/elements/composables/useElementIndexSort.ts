import {computed, onMounted, type Ref, watch} from 'vue';
import {router} from '@inertiajs/vue3';
import {index} from '@/routes/craft/cp/content/index.js';
import {useServerSort} from '@/modules/admin-table/composables/useServerSort';
import type {SortItem} from '@/common/types';
import type {ViewState} from '@/modules/elements/types/view-state';

interface ElementIndexSortContext {
  page: string;
  sectionHandle?: string | number;
  sort: Array<SortItem>;
}

interface UseElementIndexSortOptions {
  /** Called with the server-confirmed sort, e.g. to keep a filter form in sync. */
  onSortChange?: (sort: Array<SortItem>) => void;
}

/**
 * The index supports a single sort, stored as an array (so multi-column sort
 * can be enabled later). Keep only valid items (those with a field) and clamp
 * to the first — this also self-heals any extra/garbage entries that may have
 * accumulated in persisted state.
 */
function normalizeSort(items: Array<SortItem> | undefined): Array<SortItem> {
  return (items ?? []).filter((item) => !!item?.field).slice(0, 1);
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
  options: UseElementIndexSortOptions = {}
) {
  const routeArgs = () => ({
    page: props.page ?? '',
    sectionHandle: props.sectionHandle ?? undefined,
  });

  const {sortingState, sortingConfig, onSortingChange} = useServerSort({
    initialState: normalizeSort(props.sort ?? viewState.value.sort),
    onChange: ({query}) => {
      router.visit(index(routeArgs(), {query}), {
        only: ['data', 'sort', 'pagination'],
        preserveState: true,
        preserveScroll: true,
      });
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
      viewState.value.sort = next;
      options.onSortChange?.(next);
    }
  );

  // On load, if the URL doesn't specify a sort but we have one persisted from a
  // previous visit, restore it into the URL (without adding a history entry).
  onMounted(() => {
    const params = new URLSearchParams(window.location.search);
    const persisted = normalizeSort(viewState.value.sort);

    // The sort is serialized as `sort[0][field]`, `sort[0][direction]`, … so we
    // can't look for a literal `sort` key — check for any bracketed sort param.
    const hasSortInUrl = [...params.keys()].some(
      (key) => key === 'sort' || key.startsWith('sort[')
    );

    if (hasSortInUrl || !persisted.length) {
      return;
    }

    if (
      JSON.stringify(persisted) === JSON.stringify(normalizeSort(props.sort))
    ) {
      return;
    }

    router.visit(
      index(routeArgs(), {
        query: {
          ...Object.fromEntries(params),
          sort: sortItemsToQuery(persisted),
        },
      }),
      {
        only: ['data', 'sort', 'pagination'],
        preserveState: true,
        preserveScroll: true,
        replace: true,
      }
    );
  });

  // Two-way bindings for the single-column sort controls in the "View" popover.
  // They read from and write through the same sorting state as the column
  // headers, so the popover, the headers, the URL, and local storage stay in
  // sync.
  const sortField = computed<string>({
    get: () => sortingState.value[0]?.id ?? 'title',
    set: (field) =>
      onSortingChange([
        {id: field, desc: sortingState.value[0]?.desc ?? false},
      ]),
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
  };
}
