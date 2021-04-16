interface ProgressBarInterface
{
    new($element: JQuery, displaySteps?: boolean): ProgressBarInterface

    $progressBar: JQuery

    setItemCount(count: number): void

    setProcessedItemCount(count: number): void

    updateProgressBar(): void

    showProgressBar(): void
}

declare var Craft: {
    ProgressBar: ProgressBarInterface,
    t(category: string, message: string, params?: object): string,
    postActionRequest(action: string, data?: object, callback?: (response: object, textStatus: string) => void): void,
    initUiElements($container: JQuery): void,
    expandPostArray(arr: object): any,
    LoginForm: LoginForm,
    cpLoginChain: string,
    PasswordInput: any
};

declare var Garnish: any;
