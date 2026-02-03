/**
 * This is just a temp file copied from @craftcms/webpack to make typescript
 * happy for the moment.
 */
import type {CpGlobal} from '@craftcms/cp/src/types/globals';

// Set up interfaces and types
interface ProgressBarInterface {
  new (
    $element: JQuery,
    displaySteps?: boolean,
    settings?: Object
  ): ProgressBarInterface;

  $progressBar: JQuery;

  setItemCount(count: number): void;

  setProcessedItemCount(count: number): void;

  updateProgressBar(): void;

  showProgressBar(): void;
}

interface IntervalManagerInterface {
  new (settings?: Object): IntervalManagerInterface;

  stop(): void;

  start(): void;
}

type Site = {
  handle: string;
  id: number;
  name: string;
  uid: string;
};

interface CraftGlobal extends CpGlobal {
  csrfTokenName?: string;
  csrfTokenValue?: string;
  ProgressBar: ProgressBarInterface;
  IntervalManager: IntervalManagerInterface;
  t(message: string, params?: object, category?: string): string;
  sendActionRequest(method: string, action: string, options?: object): Promise;
  initUiElements($container: JQuery): void;
  expandPostArray(arr: object): any;
  escapeHtml(str: string);
  sites: Site[];
  Preview: any;
  setCookie(name: string, value: string): any;
  getCookie(name: string): any;
  getUrl(path: string, params?: string | object, baseUrl?: string): string;
  cp?: {
    jobInfo?: unknown[];
    displayedJobInfo?: unknown;
    totalJobs?: number;
    trigger?: (event: string, data?: unknown) => void;
  };
  systemUid?: string;
  canAccessQueueManager?: boolean;
  queue?: {
    hasWaitingJobs?: boolean;
    hasReservedJobs?: boolean;
  };
}

// Declare existing variables, mock the things we'll use.
declare var Craft: CraftGlobal;

declare global {
  interface Window {
    bootedCallbacks: Array<(craft: any) => void>;
    bootingCallbacks: Array<(craft: any) => void>;
    CpConfig: Record<string, any>;
    Craft?: CraftGlobal;
  }
}

declare var Garnish: any;
declare type JQuery = any;
declare var $: any;
