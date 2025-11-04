declare const d3: any | undefined;
declare const d3FormatLocaleDefinition: any | undefined;

interface ProgressBarInterface {
  $progressBar: JQuery;

  new (
    $element: JQuery,
    displaySteps?: boolean,
    settings?: Object
  ): ProgressBarInterface;

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

interface CraftGlobal {
  csrfTokenName?: string;
  csrfTokenValue?: string;
  ProgressBar: ProgressBarInterface;
  IntervalManager: IntervalManagerInterface;
  t(category: string, message: string, params?: object): string;
  sendActionRequest(
    method: string,
    action: string,
    options?: object
  ): Promise<any>;
  initUiElements($container: JQuery): void;
  expandPostArray(arr: object): any;
  escapeHtml(str: string): string;
  sites: Site[];
  Preview: any;
  cp: any;
  setCookie(name: string, value: string, options?: object): void;
  getCookie(name: string): string;
  baseApiUrl: string;
  apiParams: null | Record<any, any>;
  httpProxy?: null | Record<string, any>;
  registeredAssetBundles?: Array<string>;
  registeredJsFiles?: Array<string>;
}

declare var Craft: CraftGlobal;

declare global {
  interface Window {
    Craft: CraftGlobal;
  }
}
