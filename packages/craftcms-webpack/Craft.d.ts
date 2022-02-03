// Set up interfaces and types
interface ProgressBarInterface {
    new($element: JQuery, displaySteps?: boolean): ProgressBarInterface

    $progressBar: JQuery

    setItemCount(count: number): void

    setProcessedItemCount(count: number): void

    updateProgressBar(): void

    showProgressBar(): void
}

interface ElevatedSessionManagerInterface
{
    fetchingTimeout: boolean,
    requireElevatedSession(success: () => void, failure?: () => void)
}

// Declare existing variables, mock the things we'll use.
declare var Craft: {
    ProgressBar: ProgressBarInterface,
    t(category: string, message: string, params?: object): string,
    postActionRequest(action: string, data?: object, callback?: (response: object, textStatus: string) => void): void,
    initUiElements($container: JQuery): void,
    expandPostArray(arr: object): any,
    AuthenticationChainHandler: AuthenticationChainHandler,
    cpLoginChain: string,
    PasswordInput: any,
    appendFootHtml: (html?: string) => void
    elevatedSessionManager: ElevatedSessionManagerInterface
};

declare var Garnish: any;
declare type JQuery = any;
declare var $: any;
