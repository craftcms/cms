import {QueueService} from '@src/services/Queue';
import {ConfigService} from '@src/services/Config';

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

export interface CpGlobal {
  $queue: QueueService;
  $config: ConfigService;
}
