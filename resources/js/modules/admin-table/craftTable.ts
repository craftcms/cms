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
} from '@tanstack/vue-table';

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
 * Creates a CP table: TanStack's `useTable` with the CP's features and
 * defaults already applied, so a table only supplies what's its own.
 */
export const useCraftTable = craftTableHook.useAppTable;
