/**
 * Types for the element selector core.
 *
 * This folder is deliberately free of `lit`, `vue`, `jquery` and the `Craft`
 * global — it is the business layer both the web component and the Vue modal
 * bind to, and third-party code can drive it directly. The `core` vitest project
 * runs on the `node` environment to keep that honest.
 */

/**
 * What a selection hands back.
 *
 * The six named keys are the stable contract, produced by
 * `ModalIndexViewModel::extraRowData()` on the server. The index signature
 * carries whatever else that method emits for a given element type — `kind` and
 * `alt` for assets, `folderId` for folders — so a consumer that needs more than
 * the common six gets it without a change here.
 */
export interface ElementInfo {
  id: number;
  siteId: number | null;
  label: string;
  status: string | null;
  url: string | null;
  hasThumb: boolean;
  [key: string]: unknown;
}

/** The `element-selector-modals/body` response. */
export interface ElementIndexBody {
  /** Legacy `ElementIndexHtml` markup. Only the volume-folder path reads it. */
  html: string;
  /** `ModalIndexViewModel::toArray()`. The Vue index reads it. */
  props: Record<string, unknown>;
}

/** Loads the index payload. Injectable so the core is testable without axios. */
export type LoadIndexBody = (
  action: string,
  params: Record<string, unknown>
) => Promise<ElementIndexBody>;

/**
 * The seam between the controller and whatever is rendering the index.
 *
 * The adapter *pushes* selection in via {@link ElementSelectorController.setSelection};
 * the controller only ever asks it to clear or tear down. That direction is what
 * lets one controller drive a Vue index, a legacy jQuery index, or a test stub
 * without knowing which it has.
 */
export interface ElementIndexAdapter {
  clearSelection(): void;
  destroy?(): void;
}

/** Extra information about a selection, beyond the elements themselves. */
export interface SelectMeta {
  /** Set by `AssetSelectorController` when a transform was chosen. */
  transform?: string | null;
}

export interface ElementSelectorOptions {
  /** Fully-qualified element class, e.g. `CraftCms\Cms\Entry\Elements\Entry`. */
  elementType: string;

  // — Query —
  sources?: string[] | null;
  condition?: unknown;
  criteria?: Record<string, unknown> | null;
  referenceElementId?: number | null;
  referenceElementOwnerId?: number | null;
  referenceElementSiteId?: number | null;
  /** `null` or `'auto'` leaves the decision to the server. */
  showSiteMenu?: boolean | 'auto' | null;
  siteIds?: number[] | null;

  // — Selection rules —
  multiSelect?: boolean;
  disabledElementIds?: number[];
  /** Add each selection to the disabled set, so it can't be picked twice. */
  disableElementsOnSelect?: boolean;
  hideOnSelect?: boolean;

  // — Copy the chrome renders —
  modalTitle?: string | null;
  showTitle?: boolean;
  selectBtnLabel?: string | null;
  fullscreen?: boolean;

  // — Index bootstrapping —
  bodyAction?: string;
  storageKey?: string | null;
  indexSettings?: Record<string, unknown>;
  defaultSiteId?: number | null;
  defaultSource?: string | null;
  defaultSourcePath?: unknown[] | null;
  preferStoredSource?: boolean;
  showSourcePath?: boolean;
  hideSidebar?: boolean;

  /**
   * Called with the chosen elements.
   *
   * May return a promise. The controller awaits it and holds `busy` for its
   * duration, which is what replaced the old `disable()` / `disableCancelBtn()` /
   * `disableSelectBtn()` / `showFooterSpinner()` quartet — and, unlike that
   * quartet, releases on a throw instead of stranding the buttons.
   */
  onSelect?: (
    elements: ElementInfo[],
    meta: SelectMeta
  ) => void | Promise<void>;
  onCancel?: () => void;
  onClose?: () => void;

  /** Test seam; defaults to a POST through `actionClient`. */
  loadIndexBody?: LoadIndexBody;
}

/** Every option resolved against its default. */
export type ResolvedElementSelectorOptions = ElementSelectorOptions &
  Required<
    Pick<
      ElementSelectorOptions,
      | 'multiSelect'
      | 'disabledElementIds'
      | 'disableElementsOnSelect'
      | 'hideOnSelect'
      | 'showTitle'
      | 'fullscreen'
      | 'bodyAction'
      | 'indexSettings'
      | 'preferStoredSource'
      | 'showSourcePath'
      | 'hideSidebar'
    >
  > & {
    modalTitle: string;
    selectBtnLabel: string;
  };

/** An immutable snapshot. A fresh one is built for every `change` event. */
export interface ElementSelectorState {
  readonly open: boolean;
  /** An index body request is in flight. */
  readonly loading: boolean;
  /** A submit is in flight; both footer buttons are unavailable. */
  readonly busy: boolean;
  readonly selection: readonly ElementInfo[];
  readonly disabledElementIds: readonly number[];
  readonly indexBody: ElementIndexBody | null;
  readonly error: Error | null;
  readonly title: string;
  readonly showTitle: boolean;
  readonly selectLabel: string;
  readonly canSubmit: boolean;
  readonly canCancel: boolean;
}

export interface ElementSelectorEventMap {
  /** Any state change. The only event a presentation layer needs. */
  change: ElementSelectorState;
  open: void;
  close: void;
  cancel: void;
  select: {elements: ElementInfo[]; meta: SelectMeta};
  error: Error;
}

export type ElementSelectorEvent = keyof ElementSelectorEventMap;

export type ElementSelectorListener<E extends ElementSelectorEvent> = (
  data: ElementSelectorEventMap[E]
) => void;
