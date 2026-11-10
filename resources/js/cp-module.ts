import {defineAsyncComponent, type Ref} from 'vue';
import {ConfigService} from '@craftcms/ui/services/Config';
import type {InertiaForm} from '@inertiajs/vue3';
import useInternalCraftData, {
  type CpUser as InternalCpUser,
  type CraftData as InternalCraftData,
} from './common/composables/useCraftData';
import type {UrlMethodPair} from '@inertiajs/core';
import {assistantApi} from './bootstrap/assistant';
import type {FormSaveOptions} from './common/types';
import {
  useSettingsSave as useSettingsSaveWith,
  type UseSettingsSaveOptions,
} from './modules/settings/composables/useSettingsSave';
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

/** Building blocks for a plugin's own pages. */

/** A link to another control panel page, loaded with Inertia. */
export const CpLink = defineAsyncComponent(
  () => import('./common/components/CpLink.vue')
);

/** Pads page content to line up with the control panel's header. */
export const CpContainer = defineAsyncComponent(
  () => import('./common/components/CpContainer.vue')
);

/** A dialog. Escape and the overlay close only the topmost one. */
export const Modal = defineAsyncComponent(
  () => import('./common/components/Modal.vue')
);

/** A modal holding a form, with a title and Cancel and Save buttons. */
export const ModalForm = defineAsyncComponent(
  () => import('./common/components/ModalForm.vue')
);

/** The Save button and save menu for an Inertia form. */
export const FormActions = defineAsyncComponent(
  () => import('./common/components/FormActions.vue')
);

/** Lists a failed save's errors, and moves focus to it. */
export const ErrorSummary = defineAsyncComponent(
  () => import('./common/form/ErrorSummary.vue')
);

/** A field's tip, with an optional “Learn more” link. */
export const FieldTip = defineAsyncComponent(
  () => import('./common/components/FieldTip.vue')
);

/** Shows the messages sent to a target beside the control that sent them. */
export const InlineFlash = defineAsyncComponent(
  () => import('./common/components/InlineFlash.vue')
);

/** Says settings can't be changed in this environment. */
export const CalloutReadOnly = defineAsyncComponent(
  () => import('./common/components/CalloutReadOnly.vue')
);

/** A loading placeholder block. */
export const PlaceholderShape = defineAsyncComponent(
  () => import('./common/components/PlaceholderShape.vue')
);

/** Syntax-highlighted code. */
export const CodeHighlight = defineAsyncComponent(
  () => import('./common/components/CodeHighlight.vue')
);

/** A button that opens a control panel page in a slideout. */
export const SlideoutButton = defineAsyncComponent(
  () => import('./common/components/SlideoutButton.vue')
);

/** Puts server-rendered markup on the page, with the assets it needs. */
export const HtmlFragmentRenderer = defineAsyncComponent(
  () => import('./common/components/HtmlFragmentRenderer.vue')
);

/** A dropdown that picks one option. */
export const Select = defineAsyncComponent(
  () => import('./common/form/Select.vue')
);

/** Checkboxes that pick several options, which can be sortable. */
export const CheckboxGroup = defineAsyncComponent(
  () => import('./common/form/CheckboxGroup.vue')
);

/** A text input that suggests values as the user types. */
export const Combobox = defineAsyncComponent(
  () => import('./common/form/CraftCombobox.vue')
);

/** Picks an icon from a searchable list. */
export const IconPicker = defineAsyncComponent(
  () => import('./common/form/IconPicker.vue')
);

/** Previous and next page buttons, a page number, and an optional page size. */
export const PaginationControls = defineAsyncComponent(
  () => import('./common/components/PaginationControls.vue')
);

export type {ModalProps, ModalWidth} from './common/components/modal';
export type {HtmlFragment} from './common/types';

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
export {messageTargetHeaders} from './modules/messages';
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
 * Where a plugin adds to the control panel: its Inertia pages, the components
 * the server refers to by name, element details panels, and work to do around
 * the CP's boot. Plugin bundles run before the CP boots, so registering from a
 * module's top level is fine.
 */
export {onBooted, onBooting} from './bootstrap/lifecycle';
export {registerPage, type PageRegistration} from './bootstrap/inertia-pages';
export {
  registerComponent,
  type CpComponentLoader,
  type CpComponentRegistration,
} from './bootstrap/components';
export {
  registerElementDetailsPanel,
  type ElementDetailsPanelContext,
  type ElementDetailsPanelDescriptor,
  type ElementDetailsPanelStatus,
} from './bootstrap/element-details-panels';
export type {ElementEditPayload} from './modules/elements/composables/useElementEditor';

/**
 * The AI assistant: adds to the context the chat sends, offers context in its
 * message box's Add menu and as-you-type suggestions, and opens the chat.
 */
export const Assistant = {
  addContext: assistantApi.addContext,
  getContext: assistantApi.getContext,
  removeContext: assistantApi.removeContext,
  registerContextSource: assistantApi.registerContextSource,
  registerTextExpander: assistantApi.registerTextExpander,
  open: assistantApi.open,
};
export type {
  AssistantContextSource,
  AssistantTextExpander,
} from './bootstrap/assistant';
export type {
  AssistantContextInput,
  AssistantContextItem,
} from './modules/ai/assistant/context';

/**
 * The Craft UIs mounted in the control panel, and how a plugin's own controls
 * take part in copying and pasting field values.
 */
export {uiValues as UiValues} from './modules/ui/ui-values';
export type {UiValueChangeDetail, UiValueSource} from './modules/ui/ui-values';
export type {UiValueAcceptor, UiValueSnapshot} from './modules/ui/valueAccess';
export type {UiValuePreview} from './modules/ui/valuePreviews';

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
export {getActionUrl, getCpUrl, getSiteUrl, getUrl} from './common/craft/url';

/** Telling a failed request from a cancelled one, and the types `http` uses. */
export {
  HttpCancelledError,
  HttpError,
  isCancel,
  isHttpError,
  type HttpClient,
  type HttpErrorCode,
  type HttpHeaders,
  type HttpParams,
  type HttpRequestConfig,
  type HttpResponse,
} from '@craftcms/ui/utilities/api/http';

/** Requests as reactive state: data, error, loading and the rest. */
export {
  useActionClient,
  useFetch,
  usePost,
  type FetchState,
  type UseFetchOptions,
  type UseFetchReturn,
} from './common/composables/useFetch';

/** Dates, numbers and money in the user's formatting locale. */
export {
  formatCurrency,
  formatDate,
  formatNumber,
  formattingLocale,
} from '@craftcms/ui/utilities/format';

/** Handles, slugs and environment variable names, as Craft makes them. */
export {
  asciiString,
  toEnvVar,
  toHandle,
  toUriFormat,
} from '@craftcms/ui/utilities/string';
export {useInputGenerator} from './common/composables/useInputGenerator';

/** The signed-in user, as plugins see them. */
export type CpUser = Pick<
  InternalCpUser,
  'id' | 'username' | 'email' | 'name' | 'admin'
>;

/**
 * The parts of the control panel's page data plugins can rely on. The page
 * carries more, for the control panel's own use.
 */
export interface CraftData {
  currentUser: CpUser | null;
  site: InternalCraftData['site'];
  system: InternalCraftData['system'];
  app: InternalCraftData['app'];
  allowAdminChanges: boolean;
  devMode: boolean;
  maintenanceMode: boolean;
  orientation: 'ltr' | 'rtl';
}

/** The control panel's page data, as refs that follow the page. */
export const useCraftData: () => {
  [Key in keyof CraftData]-?: Readonly<Ref<CraftData[Key]>>;
} = useInternalCraftData;

/**
 * Saves a settings form the way Craft's own settings screens do: Cmd/Ctrl + S,
 * elevated sessions, validation errors, and saving from a slideout without
 * leaving the page behind it. `action` returns where to submit, such as a
 * Wayfinder route.
 */
export function useSettingsSave<T extends object>(
  form: InertiaForm<T>,
  action: () => UrlMethodPair | string,
  options: UseSettingsSaveOptions<T> = {}
): {save: (options?: FormSaveOptions) => void} {
  return useSettingsSaveWith(form, action, options);
}
export type {UseSettingsSaveOptions};

/** Browser storage scoped to this Craft install. */
export {
  useLocalStorage,
  useSessionStorage,
  useStorage,
} from './common/composables/useStorage';

/** Keyboard shortcuts, listed in the command palette's shortcut list. */
export {
  registerShortcut,
  useShortcut,
  type ShortcutCallback,
  type ShortcutOptions,
  type ShortcutRegistration,
} from './common/shortcuts';
export {commandShortcut, usesCommandKey} from './common/utils/keyShortcuts';

/** Messages to the control panel's other tabs, such as "this entry was saved". */
export {
  broadcast,
  onBroadcast,
  type BroadcastMessage,
  type OnBroadcastOptions,
} from './common/craft/broadcast';

/** Layout: the control panel's breakpoints and writing direction. */
export {useCpBreakpoints} from './common/composables/useCpBreakpoints';
export {isRtl, startSide} from './common/craft/direction';

/** Focus handling for overlays a plugin builds itself. */
export {focusableWithin, trapFocus} from '@craftcms/ui/utilities/focus-trap';
export {
  focusReturnFor,
  type FocusReturnResolver,
} from '@craftcms/ui/utilities/focus-return';

/** Reading forms and showing a value to copy. */
export {
  isVisible,
  serializeFormInputs,
  serializeFormInputsAsObject,
} from '@craftcms/ui/utilities/dom';
export {
  showCopyTextPrompt,
  type CopyTextPromptConfig,
} from '@craftcms/ui/utilities/copy-text-prompt';

/** Element type names, and custom elements that boot a controller around server-rendered markup. */
export {elementTypeName} from './common/craft/element-types';
export {ControllerElement, defineElement} from './common/web-components';

/** The shapes of the menus, options, sites and forms the components above take. */
export type {
  ActionItemBadge,
  ActionItemButton,
  ActionItemGroup,
  ActionItemHr,
  ActionItems,
  BaseOption,
  BreadcrumbItem,
  CheckboxOption,
  ChipIndicator,
  FormAction,
  FormSaveOptions,
  OptionData,
  SelectItem,
  SelectOptGroup,
  SelectOption,
  Site,
  SiteGroup,
  UserGroup,
} from './common/types';
export type {QueryParams, QueryValue} from './common/types/query';

/**
 * The control panel's own config — `siteId`, `sites`, `userIsAdmin`,
 * `elementTypeNames`, `orientation` and the rest of the payload the control
 * panel boots with.
 */
export function cpConfig(): ConfigService {
  return ConfigService.getInstance();
}

/** Slideouts, element editors, chip actions and thumbnails. */
export {openSlideout, createElementEditor} from './common/slideouts';
export type {ElementEditorPanel} from './common/slideouts';
export type {
  OpenSlideoutOptions,
  SlideoutSaveResult,
} from './common/slideouts/types';
export {addActionsToChip, type ChipAction} from './common/craft/chip-actions';
export {loadElementThumbs} from './modules/element-thumb-loader';
export {openAssetImageEditor} from './modules/image-editor/open-asset-image-editor';
export {Uploader} from './modules/uploader/uploader';
