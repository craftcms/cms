import {QueueService} from '@src/services/Queue';
import {ConfigService} from '@src/services/Config';

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

export interface CpServices {
  $queue: QueueService;
  $config: ConfigService;
}

declare global {
  // eslint-disable-next-line no-var
  var d3: any | undefined;
  // eslint-disable-next-line no-var
  var d3FormatLocaleDefinition: any | undefined;

  // eslint-disable-next-line no-var
  var Cp: {
    registeredAssetBundles: Iterable<string>;
    registeredJsFiles: Iterable<string>;
    csrfTokenValue?: string;
    apiParams?: Record<string, unknown>;
    httpProxy?: unknown;
  };

  interface Window {
    Craft?: {
      setCookie(name: string, value: string): void;
    };
  }
}

export {};
