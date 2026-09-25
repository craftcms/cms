import {
  columnOrderingFeature,
  columnSizingFeature,
  columnVisibilityFeature,
  rowPaginationFeature,
  rowSelectionFeature,
  rowSortingFeature,
  tableFeatures,
} from '@tanstack/vue-table';

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
});

export type CraftTableFeatures = typeof craftTableFeatures;
