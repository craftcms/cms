import {defineAsyncComponent} from 'vue';

/**
 * The CP's element index and editor, its admin table, and the shared components
 * CP pages are built from, published to plugin bundles through the import map
 * as `@craftcms/cp` (see `Cp::sharedModules()`), so a plugin's own
 * pages can use the same components the CP's pages do.
 *
 * The components load through dynamic imports so Vite's preload helper brings
 * their CSS along with them, the way the CP's own pages get theirs — an entry
 * reached through the import map has no stylesheet links of its own.
 */
export const ElementIndexPage = defineAsyncComponent(
  () => import('./modules/elements/index/components/ElementIndexPage.vue')
);

export const ElementEditor = defineAsyncComponent(
  () => import('./modules/elements/components/ElementEditor.vue')
);

export const CpButtonLink = defineAsyncComponent(
  () => import('./common/components/CpButtonLink.vue')
);

export const ActionMenu = defineAsyncComponent(
  () => import('./common/components/ActionMenu.vue')
);

export const AdminTable = defineAsyncComponent(
  () => import('./modules/admin-table/components/AdminTable.vue')
);

export const SearchForm = defineAsyncComponent(
  () => import('./modules/admin-table/components/SearchForm.vue')
);

export const DeleteButton = defineAsyncComponent(
  () => import('./modules/admin-table/components/DeleteButton.vue')
);

export type {ActionItem, ActionItemLink} from './common/types';

/**
 * Lets a plugin's page configure the control panel shell around it — its
 * secondary nav, most usefully, so an index can put its own sources there.
 */
export {useAppLayout} from './common/composables/useAppLayout';

export {
  useCustomizeSources,
  type CustomizeSourcesTarget,
} from './modules/elements/index/composables/useCustomizeSources';

export {
  appendIndexQuery,
  type ElementIndexRoute,
  type IndexQueryParams,
} from './modules/elements/index/composables/useElementIndexVisits';

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
