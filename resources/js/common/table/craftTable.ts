import {
  columnOrderingFeature,
  columnSizingFeature,
  columnVisibilityFeature,
  createTableHook,
  metaHelper,
  rowPaginationFeature,
  rowSelectionFeature,
  rowSortingFeature,
  tableFeatures,
  type RowData,
} from '@tanstack/vue-table';
import type {RequestPayload, UrlMethodPair} from '@inertiajs/core';
import {router} from '@inertiajs/vue3';
import {watch} from 'vue';
import type {PaginationData, SortItem} from '@/common/types';
import {useServerPagination} from './useServerPagination';
import {useServerSort} from './useServerSort';

/** What a CP table's column definitions can carry in their `meta`. */
export interface CraftColumnMeta {
  wrap?: boolean;
  // Applies classes to the cell
  cellClass?: string | Record<string, boolean>;
  cellTag?: 'td' | 'th';
  headerTip?: string;
  headerSrOnly?: boolean;
  // Applies classes to the header
  headerClass?: string | Record<string, boolean>;
  // Applies classes to both the header and cell at once
  columnClass?: string | Record<string, boolean>;
  trackSize?: string;
}

/**
 * The TanStack Table features every CP table is built with.
 *
 * Tables all render through the shared `DataTable`/`BaseElementIndex`, which
 * reach for the selection, sorting, pagination, visibility and ordering APIs
 * regardless of whether a given table makes use of them — and TanStack only
 * adds a feature's APIs to a table that registers it. Sorting and pagination
 * happen on the server, so no client-side row models are registered here.
 */
export const craftTableFeatures = tableFeatures({
  columnOrderingFeature,
  columnSizingFeature,
  columnVisibilityFeature,
  rowPaginationFeature,
  rowSelectionFeature,
  rowSortingFeature,
  columnMeta: metaHelper<CraftColumnMeta>(),
});

export type CraftTableFeatures = typeof craftTableFeatures;

const craftTableHook = createTableHook({
  features: craftTableFeatures,
  // With no client-side sorting, a sortable column would only flip its arrow.
  // Tables that sort on the server opt back in through `useServerSort`.
  enableSorting: false,
});

/**
 * Pages and sorts a table on the server, through Inertia visits to the page's
 * own URL that reload its rows, `pagination` and `sort` props together.
 */
export interface CraftTableInertiaOptions {
  url: string | UrlMethodPair;
  /** Leave out for a table that isn't paginated. */
  pagination?: () => PaginationData;
  /** Leave out for a table that isn't sortable. */
  sort?: () => SortItem[];
  /** The prop holding the table's rows. */
  dataProp?: string;
}

export type CraftTableOptions<TData extends RowData> = Parameters<
  typeof craftTableHook.useAppTable<TData>
>[0] & {inertia?: CraftTableInertiaOptions};

/**
 * Creates a CP table: TanStack's `useTable` with the CP's features and
 * defaults already applied, so a table only supplies what's its own.
 */
export function useCraftTable<TData extends RowData>(
  options: CraftTableOptions<TData>
) {
  return craftTableHook.useAppTable<TData>(
    options.inertia ? withInertia(options, options.inertia) : options
  );
}

/**
 * Options are often getters over props, so they're carried across as property
 * descriptors; spreading them would read each one once and freeze it.
 */
function withInertia<T extends {state?: object}>(
  options: T,
  inertia: CraftTableInertiaOptions
): T {
  const only = [inertia.dataProp ?? 'data', 'pagination', 'sort'];
  const visit = (query: RequestPayload) =>
    router.visit(inertia.url, {
      data: query,
      only,
      preserveScroll: true,
      preserveState: true,
    });

  const config: PropertyDescriptorMap = {};
  const state: PropertyDescriptorMap = Object.getOwnPropertyDescriptors(
    options.state ?? {}
  );

  if (inertia.pagination) {
    const pagination = inertia.pagination;
    const {paginationState, onPaginationChange} = useServerPagination({
      initialState: pagination(),
      onChange: ({query}) => visit(query),
    });
    watch(pagination, (next) => {
      paginationState.value = {
        pageIndex: next.current_page - 1,
        pageSize: next.per_page,
      };
    });

    state.pagination = {get: () => paginationState.value, enumerable: true};
    config.manualPagination = {value: true, enumerable: true};
    config.onPaginationChange = {value: onPaginationChange, enumerable: true};
    config.rowCount = {get: () => pagination().total, enumerable: true};
  }

  if (inertia.sort) {
    const sort = inertia.sort;
    const {sortingState, sortingConfig} = useServerSort({
      initialState: sort(),
      onChange: ({query}) => visit(query),
    });
    watch(sort, (next) => {
      sortingState.value = next.map((item) => ({
        id: item.field,
        desc: item.direction === 'desc',
      }));
    });

    state.sorting = {get: () => sortingState.value, enumerable: true};
    Object.assign(config, Object.getOwnPropertyDescriptors(sortingConfig));
  }

  // The table's own options win, so it can still adjust the server defaults.
  const own: PropertyDescriptorMap = Object.getOwnPropertyDescriptors(options);
  delete own.state;
  delete own.inertia;

  return Object.defineProperties({} as T, {
    ...config,
    ...own,
    state: {value: Object.defineProperties({}, state), enumerable: true},
  });
}

const headerIdPrefixes = new WeakMap<object, string>();
let nextHeaderIdPrefix = 0;

/**
 * The id of a table's column header cell. Scoped to the table, so several
 * tables on one page can share column ids without their headers colliding.
 */
export function tableHeaderId(table: object, columnId: string): string {
  let prefix = headerIdPrefixes.get(table);
  if (prefix === undefined) {
    prefix = `table-${nextHeaderIdPrefix++}`;
    headerIdPrefixes.set(table, prefix);
  }

  return `${prefix}-header-${columnId}`;
}
