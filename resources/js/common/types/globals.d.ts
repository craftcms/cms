import type {ConfigService} from '@craftcms/ui';
import type {QueueService} from '@/modules/queue/queue';
import type {CpComponentRegistry} from '@/bootstrap/components';
import type {InertiaPageRegistry} from '@/bootstrap/inertia-pages';
import type {AxiosRequestConfig, AxiosResponse} from 'axios';

type LegacySettingValue =
  | string
  | number
  | boolean
  | null
  | undefined
  | Element
  | JQuery
  | LegacyWidgetSettings
  | LegacySettingValue[]
  | ((...args: any[]) => void);

interface LegacyWidgetSettings {
  [key: string]: LegacySettingValue;
}

interface ElementIndexInstance {
  settings: LegacyWidgetSettings;
}

interface LegacyModalInstance {
  destroy?(): void;
}

declare module '@tanstack/vue-table' {
  interface ColumnMeta {
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
}

// Set up interfaces and types
interface ProgressBarInterface {
  // oxlint-disable-next-line @typescript-eslint/no-misused-new
  new (
    $element: JQuery,
    displaySteps?: boolean,
    settings?: LegacyWidgetSettings
  ): ProgressBarInterface;

  $progressBar: JQuery;

  setItemCount(count: number): void;

  setProcessedItemCount(count: number): void;

  updateProgressBar(): void;

  showProgressBar(): void;
}

interface IntervalManagerInterface {
  // oxlint-disable-next-line @typescript-eslint/no-misused-new
  new (settings?: LegacyWidgetSettings): IntervalManagerInterface;

  stop(): void;

  start(): void;
}

type Site = {
  handle: string;
  id: number;
  name: string;
  uid: string;
};

interface CpServices {
  $queue: QueueService;
  $config: ConfigService;
}

interface CpStatic extends CpServices {
  $components: CpComponentRegistry;
  $inertia: InertiaPageRegistry;
}

interface CpNotificationSettings {
  icon: string;
  iconLabel: string;
  details: string;
}

interface SlideoutInstance {
  $container: JQuery;
  open(): void;
  close(): void;
  destroy(): void;
  on<T = unknown>(event: string, callback: (event: T) => void): void;
}

interface ElementSelectorModalInstance {
  show(): Promise<void>;
  hide(): void;
  destroy(): void;
  /** Returns an unsubscribe function. */
  on(event: string, callback: (data?: unknown) => void): () => void;
  /** Republishes the whole set; the index re-reads it. */
  setDisabledElementIds(ids: number[]): void;
  setBusy(busy: boolean): void;
}

type FieldLayoutDesignerInstance = any;

interface ElementSelectorModalSettings {
  /** Accepted for compatibility; the native dialog manages stacking itself. */
  closeOtherModals?: boolean;
  criteria?: LegacyWidgetSettings;
  disabledElementIds?: number[];
  disableElementsOnSelect?: boolean;
  hideOnSelect?: boolean;
  modalTitle?: string;
  showTitle?: boolean;
  selectBtnLabel?: string;
  multiSelect?: boolean;
  /** May return a promise; the modal stays busy until it settles. */
  onSelect?: (elements: any[], meta?: {transform?: string | null}) => unknown;
  onCancel?: () => void;
  onClose?: () => void;
  showSiteMenu?: boolean | 'auto' | null;
  siteIds?: number[] | null;
  sources?: string[] | null;
  condition?: unknown;
  storageKey?: string | null;
  triggerElement?: HTMLElement | (() => HTMLElement | null) | null;
}

interface CraftStatic {
  csrfTokenName?: string;
  csrfTokenValue?: string;
  ProgressBar: ProgressBarInterface;
  IntervalManager: IntervalManagerInterface;
  t(message: string, params?: LegacyWidgetSettings, category?: string): string;
  sendActionRequest(
    method: string,
    action: string,
    options?: AxiosRequestConfig
  ): Promise<AxiosResponse>;
  namespaceId(id: string, namespace?: string | null): string;
  initUiElements(container: Element | JQuery): void;
  createElementSelectorModal(
    elementType: string,
    settings?: ElementSelectorModalSettings
  ): Promise<ElementSelectorModalInstance>;
  expandPostArray(arr: FormData | URLSearchParams): LegacyWidgetSettings;
  escapeHtml(str: string);
  sites: Site[];
  Preview: any;
  setCookie(name: string, value: string): any;
  getCookie(name: string): any;
  getUrl(
    path: string,
    params?: string | LegacyWidgetSettings,
    baseUrl?: string
  ): string;
  baseCpUrl: string;
  getCpUrl(path: string, params?: string | LegacyWidgetSettings): string;
  defaultIndexCriteria: LegacyWidgetSettings;
  siteId?: number;
  cp?: {
    jobInfo?: unknown[];
    displayedJobInfo?: unknown;
    totalJobs?: number;
    trigger?: (event: string, data?: LegacySettingValue) => void;
    $notificationContainer?: {length: number};
    copyElements?: (
      elementInfo: Array<{
        type: string;
        id: string | number;
        siteId?: number | null;
        draftId?: number | null;
        revisionId?: number | null;
        fieldId?: number | null;
        ownerId?: number | null;
      }>
    ) => void;
    displayNotification: (
      type: any,
      message?: string,
      settings?: CpNotificationSettings
    ) => object;
    displayError?: (
      message?: string | CpNotificationSettings,
      settings?: CpNotificationSettings
    ) => object;
    displayNotice?: (
      message?: string,
      settings?: CpNotificationSettings
    ) => object;
    runQueue?: () => void;
  };
  broadcaster?: {postMessage(message: LegacyWidgetSettings): void};
  defaultIndexCriteria: LegacyWidgetSettings;
  systemUid?: string;
  canAccessQueueManager?: boolean;
  queue?: {
    hasWaitingJobs?: boolean;
    hasReservedJobs?: boolean;
  };

  pageTrigger?: string;

  Slideout: {
    new (html: string, settings?: SlideoutSettings): SlideoutInstance;
    new (
      elementType: string,
      element: HTMLElement | JQuery,
      settings?: SlideoutSettings
    ): SlideoutInstance;
    new (settings: SlideoutSettings): SlideoutInstance;
  };
  CpScreenSlideout: {
    new (url: string, settings?: LegacyWidgetSettings): SlideoutInstance;
  };
  createElementEditor(elementType: string, settings?: object): SlideoutInstance;
  FieldLayoutDesigner: {
    new (
      container: HTMLElement | JQuery,
      settings?: LegacyWidgetSettings
    ): FieldLayoutDesignerInstance;
  };
  ElevatedSessionForm: {
    new (
      form: HTMLFormElement | JQuery,
      inputs?: string | string[]
    ): LegacyModalInstance;
  };
  elevatedSessionManager: {
    fetchingTimeout: boolean;
    requireElevatedSession(
      onSuccess: () => void,
      onCancel?: () => void,
      minSafeElevatedSessionTimeout?: number
    ): void | Promise<void>;
  };

  ui: {
    createCopyTextPrompt(settings: {
      label: string;
      value: string;
    }): LegacyModalInstance;
  };

  // Asset editing, still served by the legacy bundle.
  isImagick?: boolean;
  PreviewFileModal: new (
    assetId: number,
    settings?: LegacyWidgetSettings
  ) => LegacyModalInstance;
  AssetImageEditor: new (
    assetId: number,
    settings?: LegacyWidgetSettings
  ) => LegacyModalInstance;
  createUploader(
    fsType: string,
    $element: JQuery,
    settings?: LegacyWidgetSettings
  ): {setParams(params: LegacyWidgetSettings): void};
}

// oxlint-disable-next-line @typescript-eslint/no-empty-object-type
interface GarnishStatic {}
// oxlint-disable-next-line @typescript-eslint/no-empty-object-type
// Declare existing variables, mock the things we'll use.

declare global {
  let Cp: CpStatic;
  let Craft: CraftStatic;
  let Garnish: GarnishStatic;
  let $: JQueryStatic;
  interface Window {
    bootedCallbacks: Array<(craft: CraftStatic) => void>;
    bootingCallbacks: Array<(craft: CraftStatic) => void>;
    CpConfig: LegacyWidgetSettings;
    Cp: CpStatic;
    Craft: CraftStatic;
    $?: JQueryStatic;
    jQuery?: JQueryStatic;
    Garnish: GarnishStatic;
  }
}
