// Set up interfaces and types
interface ProgressBarInterface {
  new ($element: JQuery, displaySteps?: boolean): ProgressBarInterface;

  $progressBar: JQuery;

  setItemCount(count: number): void;

  setProcessedItemCount(count: number): void;

  updateProgressBar(): void;

  showProgressBar(): void;
}

type Site = {
  handle: string;
  id: number;
  name: string;
  uid: string;
};

// Declare existing variables, mock the things we'll use.
declare var Craft: {
  csrfTokenName?: string;
  csrfTokenValue?: string;
  ProgressBar: ProgressBarInterface;
  t(category: string, message: string, params?: object): string;
  sendActionRequest(method: string, action: string, options?: object): Promise;
  initUiElements($container: JQuery): void;
  expandPostArray(arr: object): any;
  escapeHtml(str: string);
  sites: Site[];
  Preview: any;
  cp: any;
};

declare var Garnish: any;
declare type JQuery = any;
declare var $: any;
