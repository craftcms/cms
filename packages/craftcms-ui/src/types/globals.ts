/**
 * Runtime globals bootstrapped by the Control Panel that aren't ES module
 * imports. Declared here so they're available across the package.
 */
declare global {
  // Optional d3 globals used by `formatNumber()` when the legacy charting
  // bundle is present on the page.
  const d3: any | undefined;
  const d3FormatLocaleDefinition: any | undefined;

  /** Control Panel runtime config exposed as a global `Cp` object. */
  interface CpGlobal {
    registeredAssetBundles: string[];
    registeredJsFiles: string[];
    apiParams?: Record<string, unknown>;
    httpProxy?: unknown;
    defaultCookieOptions?: Record<string, unknown>;
    csrfTokenValue?: string;
  }

  const Cp: CpGlobal;
}

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

/** Shape of the legacy global `Craft` object that this package relies on. */
export interface CraftGlobal {
  setCookie(name: string, value: string): void;
}

/**
 * `window` narrowed to include the optional legacy `Craft` global.
 *
 * Use this instead of augmenting the global `Window` interface — a global
 * augmentation would be bundled into the package's published types and leak a
 * conflicting `window.Craft` declaration into consuming apps.
 */
export type WindowWithCraft = Window & {Craft?: CraftGlobal};

export {};
