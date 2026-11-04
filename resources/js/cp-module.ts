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

/**
 * Renders a server-built `HtmlFragment` (HTML plus its head and body assets),
 * so a plugin's page can host HTML from template hooks or other server code.
 */
export const HtmlFragmentRenderer = defineAsyncComponent(
  () => import('./common/components/HtmlFragmentRenderer.vue')
);

/**
 * A screen's details column: an Info panel showing its default slot (sidebar
 * content) above the server's metadata HTML.
 */
export const MetadataDetails = defineAsyncComponent(
  () => import('./common/components/MetadataDetails.vue')
);

/**
 * Adds a panel to the screen's details column while it's mounted: a rail icon
 * and the panel it opens, with the default slot as the panel's content.
 */
export const ScreenPanel = defineAsyncComponent(
  () => import('./common/components/ScreenPanel.vue')
);

/**
 * An element's own actions (Delete, Copy URL, …), from its
 * `actionMenuDescriptors()`, with their behaviors dispatched client-side.
 */
export const ElementActionMenu = defineAsyncComponent(
  () => import('./modules/elements/components/ElementActionMenu.vue')
);

export type {ActionItem, ActionItemLink} from './common/types';

export {openUiModal, type UiModalOptions} from './modules/ui/open-ui-modal';

export {
  useInertiaUiRenderer,
  type UiRendererInstance,
} from './modules/ui/useInertiaUiRenderer';

/**
 * Lets a plugin's page configure the control panel shell around it — its
 * secondary nav, most usefully, so an index can put its own sources there.
 */
export {useAppLayout} from './common/composables/useAppLayout';

export {
  useInertiaReorder,
  type InertiaReorderOptions,
} from './common/composables/useInertiaReorder';

export {
  useCustomizeSources,
  type CustomizeSourcesTarget,
} from './modules/elements/index/composables/useCustomizeSources';

export {
  appendIndexQuery,
  type ElementIndexRoute,
  type IndexQueryParams,
} from './modules/elements/index/composables/useElementIndexVisits';

/**
 * Live Preview: the page's previews, their events, and refreshing them after
 * changes made outside their editor.
 */
export {
  getActivePreview,
  getPreviews,
  onPreviewEvent,
  refreshPreviews,
  watchPreviews,
  type LivePreviewEventName,
  type LivePreviewEvents,
  type LivePreviewListener,
} from './modules/elements/live-preview/previews';

export type {
  LivePreviewController,
  LivePreviewTarget,
} from './modules/elements/live-preview/useLivePreview';

export {
  useCraftTable,
  craftTableFeatures,
  type CraftTableFeatures,
  type CraftTableInertiaOptions,
  type CraftTableOptions,
  type CraftColumnMeta,
} from './common/table/craftTable';

export {
  createCraftColumnHelper,
  type CraftColumnHelper,
} from './common/table/createCraftColumnHelper';

export {
  TableAppearance,
  TableSpacing,
  type PaginationData,
  type SortItem,
  type TableAppearanceValue,
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
