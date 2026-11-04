import {defineAsyncComponent} from 'vue';
import {FileUpload, UploadError, registerTransport} from './upload-client';
import {
  closeCommandPalette,
  openCommandPalette,
  registerActions,
  registerProvider,
  registerView,
} from './modules/command-palette/registry';

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

export {
  showError,
  showMessage,
  showNotice,
  showSuccess,
  type CpMessage,
  type CpNotification,
  type MessageSettings,
  type MessageType,
} from './modules/messages/messages';
export {useMessages} from './modules/messages/useMessages';
export {announce} from './common/announcer';
export type {AnnounceOptions} from './common/announcer';
export {
  clearCopiedElements,
  copyElements,
  getCopiedElements,
  onCopiedElementsChange,
  pasteElements,
  pasteElementsLabel,
} from './modules/elements/clipboard';
export type {
  CopiedElementInfo,
  CopiedElementsListener,
} from './modules/elements/clipboard';
export {highlighter} from './common/utils/highlight-code';

export {CraftAuthChallengeForm as AuthChallengeForm} from './modules/auth/components/auth-challenge-form';

export {
  AssetSelectorController,
  ElementSelectorController,
  registerElementSelectorController,
} from '@craftcms/ui';

export {
  createElementSelectorModal,
  type ElementSelectorModalHandle,
  type ElementSelectorModalSettings,
} from './modules/element-selector-modal/create-element-selector-modal';

/** File uploads and the transport registry the control panel's uploader uses. */
export const Uploads = {FileUpload, UploadError, registerTransport};

export type {
  PrepareUpload,
  UploadOptions,
  UploadState,
  UploadTransport,
  UploadTransportContext,
} from './upload-client';

/**
 * Opens or closes the command palette, and adds client-side search providers,
 * page actions and views to it. Server-side providers, pages and actions are
 * registered in PHP.
 */
export const CommandPalette = {
  open: openCommandPalette,
  close: closeCommandPalette,
  registerProvider,
  registerActions,
  registerView,
};

export type * from './modules/command-palette/types';
export type {CommandPaletteRegistration} from './modules/command-palette/registry';

/**
 * The utilities a plugin's own bundle needs to behave like control panel code:
 * translating, talking to controller actions, and appending markup the server
 * returned. They're re-exported here, rather than left to be imported from
 * `@craftcms/ui`, so a plugin bundle shares the control panel's instances
 * instead of bundling second copies.
 */
export {t} from '@craftcms/ui/utilities/translate';
export {escapeHtml} from '@craftcms/ui/utilities/escapeHtml';
export {
  appendBodyHtml,
  appendElementHtml,
  appendHeadHtml,
  type AppendHtmlDisposer,
} from '@craftcms/ui/utilities/dom';
export {actionClient, http} from '@craftcms/ui';
export {getActionUrl, getCpUrl, getUrl} from './common/craft/url';

/** Slideouts, element editors, chip actions and thumbnails. */
export {openSlideout, createElementEditor} from './common/slideouts';
export type {ElementEditorPanel} from './common/slideouts';
export {addActionsToChip, type ChipAction} from './common/craft/chip-actions';
export {loadElementThumbs} from './modules/element-thumb-loader';
export {openAssetImageEditor} from './modules/image-editor/open-asset-image-editor';
