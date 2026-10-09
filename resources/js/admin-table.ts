import {defineAsyncComponent} from 'vue';

/**
 * The CP's admin table and the helpers its index pages build tables with,
 * published to plugin bundles through the import map as
 * `@craftcms/cms/admin-table` (see `Cp::sharedModules()`).
 *
 * Components load through dynamic imports so their CSS comes along with them;
 * see `elements.ts`.
 */
export const AdminTable = defineAsyncComponent(
  () => import('./modules/admin-table/components/AdminTable.vue')
);

export const DeleteButton = defineAsyncComponent(
  () => import('./modules/admin-table/components/DeleteButton.vue')
);

export const SearchForm = defineAsyncComponent(
  () => import('./modules/admin-table/components/SearchForm.vue')
);

export {
  useCraftTable,
  craftTableFeatures,
  type CraftTableFeatures,
  type CraftColumnMeta,
} from './common/table/craftTable';

export {
  createCraftColumnHelper,
  type CraftColumnHelper,
} from './common/table/createCraftColumnHelper';

export {useServerPagination} from './common/table/useServerPagination';
export {useServerSort} from './common/table/useServerSort';

export {
  TableSpacing,
  type PaginationData,
  type SortItem,
  type TableSpacingValue,
} from './common/types';

// Tables are created with `useCraftTable`, so plugins only need TanStack's
// types; taking them from here keeps them matched to the CP's copy.
export type {
  CellContext,
  ColumnDef,
  PaginationState,
  Row,
  SortingState,
  Table,
  Updater,
} from '@tanstack/vue-table';
