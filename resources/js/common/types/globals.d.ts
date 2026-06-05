import type {QueueService} from '@/common/services/Queue';
import type {AxiosInstance} from 'axios';

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
  // eslint-disable-next-line @typescript-eslint/no-misused-new
  new (
    $element: JQuery,
    displaySteps?: boolean,
    settings?: object
  ): ProgressBarInterface;

  $progressBar: JQuery;

  setItemCount(count: number): void;

  setProcessedItemCount(count: number): void;

  updateProgressBar(): void;

  showProgressBar(): void;
}

interface IntervalManagerInterface {
  // eslint-disable-next-line @typescript-eslint/no-misused-new
  new (settings?: object): IntervalManagerInterface;

  stop(): void;

  start(): void;
}

type Site = {
  handle: string;
  id: number;
  name: string;
  uid: string;
};

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
  on(event: string, callback: () => void): void;
}

interface CraftStatic {
  $queue: QueueService;
  $axios: AxiosInstance;
  booting(cb: (craft: CraftStatic) => void): void;
  booted(cb: (craft: CraftStatic) => void): void;
  init(): void;
  start(): Promise<void>;

  // Server config
  csrfTokenName?: string;
  csrfTokenValue?: string;
  systemUid?: string;
  systemName?: string;
  runQueueAutomatically?: boolean;
  canAccessQueueManager?: boolean;
  translations?: Record<string, Record<string, string>>;
  registeredAssetBundles?: string[];
  registeredJsFiles?: string[];

  // Legacy classes/utils
  ProgressBar: ProgressBarInterface;
  IntervalManager: IntervalManagerInterface;
  t(message: string, params?: object, category?: string): string;
  sendActionRequest(method: string, action: string, options?: object): Promise;
  initUiElements($container: JQuery): void;
  expandPostArray(arr: object): any;
  escapeHtml(str: string);
  getUrl(path: string, params?: string | object, baseUrl?: string): string;
  getActionUrl(action: string, params?: string | object): string;
  getCpUrl(path: string, params?: string | object): string;
  sites: Site[];
  Preview: any;
  setCookie(name: string, value: string): any;
  getCookie(name: string): any;
  cp?: {
    jobInfo?: unknown[];
    displayedJobInfo?: unknown;
    totalJobs?: number;
    trigger?: (event: string, data?: unknown) => void;
    displayNotification: (
      type: any,
      message?: string,
      settings?: CpNotificationSettings
    ) => object;
    displayError?: (
      message?: string | CpNotificationSettings,
      settings?: CpNotificationSettings
    ) => object;
  };
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
    new (url: string, settings?: object): SlideoutInstance;
  };

  [key: string]: any;
}

// eslint-disable-next-line @typescript-eslint/no-empty-object-type
interface GarnishStatic {}
// eslint-disable-next-line @typescript-eslint/no-empty-object-type
interface JQueryObject {}

// Declare existing variables, mock the things we'll use.

declare global {
  let Craft: CraftStatic;
  let Garnish: GarnishStatic;
  let $: any;
  interface Window {
    bootedCallbacks: Array<(craft: any) => void>;
    bootingCallbacks: Array<(craft: any) => void>;
    Craft: CraftStatic;
    $: JQueryObject;
    jQuery: JQueryObject;
    Garnish: GarnishStatic;
  }
}
