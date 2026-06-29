import type {CpServices} from '@craftcms/cp/types/globals.d.ts';
import type {CpComponentRegistry} from '@/bootstrap/components';
import type {InertiaPageRegistry} from '@/bootstrap/inertia-pages';

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
  show(): void;
  on(event: string, callback: () => void): void;
}

interface ElementSelectorModalSettings {
  closeOtherModals?: boolean;
  criteria?: Record<string, unknown>;
  hideOnSelect?: boolean;
  modalTitle?: string;
  multiSelect?: boolean;
  onSelect?: (elements: any[]) => void;
  sources?: string[];
}

interface CraftStatic {
  csrfTokenName?: string;
  csrfTokenValue?: string;
  ProgressBar: ProgressBarInterface;
  IntervalManager: IntervalManagerInterface;
  t(message: string, params?: object, category?: string): string;
  sendActionRequest(method: string, action: string, options?: object): Promise;
  initUiElements($container: JQuery): void;
  createElementSelectorModal(
    elementType: string,
    settings?: ElementSelectorModalSettings
  ): ElementSelectorModalInstance;
  expandPostArray(arr: object): any;
  escapeHtml(str: string);
  sites: Site[];
  Preview: any;
  setCookie(name: string, value: string): any;
  getCookie(name: string): any;
  getUrl(path: string, params?: string | object, baseUrl?: string): string;
  baseCpUrl: string;
  getCpUrl(path: string, params?: string | object): string;
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
    new (url: string, settings?: object): SlideoutInstance;
  };
}

// eslint-disable-next-line @typescript-eslint/no-empty-object-type
interface GarnishStatic {}
// eslint-disable-next-line @typescript-eslint/no-empty-object-type
interface JQueryObject {}

// Declare existing variables, mock the things we'll use.

declare global {
  let Cp: CpStatic;
  let Craft: CraftStatic;
  let Garnish: GarnishStatic;
  let $: any;
  interface Window {
    bootedCallbacks: Array<(craft: any) => void>;
    bootingCallbacks: Array<(craft: any) => void>;
    CpConfig: Record<string, any>;
    Cp: CpStatic;
    Craft: CraftStatic;
    $: JQueryObject;
    jQuery: JQueryObject;
    Garnish: GarnishStatic;
  }
}
