import {addContainedJsFilesToPage, LoginForm} from "./LoginForm";

import ClickEvent = JQuery.ClickEvent;
import {AuthenticationStep} from "./AuthenticationStep";

type AuthenticationStepHandler = () => AuthenticationRequestData;

export type AuthenticationRequestData = {
    [key: string]: any
}

type AuthenticationAlternatives = {
    [key: string]: any
}

type AuthenticationResponseData = {
    returnUrl?: string
    success?: boolean
    error?: string
    message?: string
    html?: string
    loginFormHtml?: string
    stepType?: string
    footHtml?: string
    stepComplete?: boolean
    alternatives?: AuthenticationAlternatives,
    passwordReset?: boolean
}

export class AuthenticationChainHandler
{
    readonly performAuthenticationEndpoint = 'authentication/perform-authentication';
    readonly startAuthenticationEndpoint = 'authentication/start-authentication';
    readonly recoverAccountEndpoint = 'users/send-password-reset-email';
    readonly loginForm: LoginForm;

    private currentStep?: AuthenticationStep;
    private recoverAccount = false;

    private authenticationSteps: {
        [key: string]: AuthenticationStep
    } = {};

    get $authenticationStep() { return $('#authentication-step');}

    public constructor(loginForm: LoginForm, additionalData?: () => { [key: string]: any })
    {
        this.loginForm = loginForm;
        this.loginForm.$loginForm.on('submit', (event) => {
            let additionalSubmittedData = additionalData ? additionalData() : {};

            if (!this.isExistingChain()) {
                additionalSubmittedData.loginName = loginForm.$username.val();
            }

            this.clearErrors();
            this.handleFormSubmit(event, additionalSubmittedData);
            event.preventDefault();
        });
    }

    /**
     * Return true if currently recovering an account.
     */
    public isRecoveringAccount() {
        return this.recoverAccount;
    }

    /**
     * Reset the authentication chain controls and anything related in the login form.
     */
    public resetAuthenticationControls()
    {
        this.$authenticationStep.empty().attr('rel', '');
        this.recoverAccount = false;
    }

    /**
     * Register an authentication step
     *
     * @param stepType
     * @param step
     */
    public registerAuthenticationStep(stepType: string, step: AuthenticationStep)
    {
        this.authenticationSteps[stepType] = step;
    }

    /**
     * Restart authentication from scratch.
     * @param event
     */
    public restartAuthentication(event?: ClickEvent) {
        this.resetAuthenticationControls();
        this.loginForm.resetLoginForm();

        // Let the backend know to let it go, turn away and slam the door.
        Craft.sendActionRequest('POST', this.startAuthenticationEndpoint);

        if (event) {
            event.preventDefault();
        }
    }

    /**
     * Toggle the account recovery form
     */
    public toggleRecoverAccount() {
        this.recoverAccount = !this.recoverAccount;

        // Presumably, the login name input is shown already.
        if (!this.isExistingChain()) {
            return;
        }

        // Determine if we have an auth step type
        let stepType;

        if (this.$authenticationStep.attr('rel')!.length > 0) {
            stepType = this.authenticationSteps[this.$authenticationStep.attr('rel')!];
        }

        if (this.recoverAccount) {
            this.$authenticationStep.addClass('hidden');
            stepType?.cleanup();
        } else {
            this.$authenticationStep.removeClass('hidden');
            this.$authenticationStep.attr('rel')!;
            stepType?.init();
        }
    }

    /**
     * Switch the current authentication step to an alternative.
     *
     * @param stepType
     */
    public switchStep(stepType: string)
    {
        if (this.loginForm.isDisabled()) {
            return;
        }

        this.loginForm.disableForm();
        this.clearErrors();

        this.updateCurrentStepType();

        const data = { alternateStep: stepType };

        this.performStep(this.performAuthenticationEndpoint, {data});
    }

    protected updateCurrentStepType()
    {
        this.currentStep = this.authenticationSteps[this.$authenticationStep.attr('rel')!]
    }

    /**
     * Perform the authentication step against the endpoint.
     *
     * @param response
     * @param textStatus
     * @protected
     */
    protected performStep(endpoint: string, data: AuthenticationRequestData)
    {
        return Craft.sendActionRequest('POST', endpoint, {data})
            .then(response => {
                const data: AuthenticationResponseData = response?.data;
                if (data?.returnUrl?.length) {
                    window.location.href = data.returnUrl;
                    // Keep the form disabled
                    return;
                }

                if (data?.message) {
                    this.loginForm.showMessage(data.message);
                }

                // Handle password reset response early and bail
                if (data?.passwordReset) {
                    this.loginForm.toggleRecoverAccountForm();
                    this.restartAuthentication();
                }

                // Ensure alternative login options are handled
                if (data?.alternatives && Object.keys(data.alternatives).length > 0) {
                    this.showAlternatives(data.alternatives);
                } else {
                    this.loginForm.hideAlternatives();
                }

                // Keep track of current step type
                if (data?.stepType){
                    this.$authenticationStep.attr('rel', data.stepType);
                }

                // Load any JS files if needed
                if (data?.footHtml) {
                    addContainedJsFilesToPage(data.footHtml);
                }

                const initStepType = (stepType: string) => {
                    if (this.authenticationSteps[stepType]) {
                        this.authenticationSteps[stepType].init();
                    }
                }

                // Display the HTML for the auth step.
                if (data?.html) {
                    this.currentStep?.cleanup();
                    this.$authenticationStep.html(data?.html);
                    initStepType(data?.stepType!);
                }

                // Display the HTML for the entire login form, in case we just started an authentication chain
                if (data?.loginFormHtml) {
                    this.currentStep?.cleanup();
                    this.loginForm.$loginForm.html(data?.loginFormHtml);
                    this.loginForm.prepareForm();
                    initStepType(data?.stepType!);
                }

                // Just in case this was the first step, remove all the misc things.
                if (data?.stepComplete) {
                    this.loginForm.hideRememberMe();
                }

            })
            .catch(({response}) => {
                const data: AuthenticationResponseData = response?.data;
                if (data?.message) {
                    this.loginForm.showError(response.data.message);
                    Garnish.shake(this.loginForm.$loginForm);
                }
            })
            .finally(() => this.loginForm.enableForm());
    }

    /**
     * Show the alternative authentication methods available at this point.
     *
     * @param alternatives
     */
    public showAlternatives(alternatives: AuthenticationAlternatives)
    {
        let html = '';

        for (const [stepType, description] of Object.entries(alternatives)) {
            html += `<li rel="${stepType}">${description}</li>`;
        }

        this.loginForm.showAlternatives(html);
    }

    /**
     * Handle the login form submission.
     *
     * @param ev
     * @param additionalData
     */
    public handleFormSubmit (ev: any, additionalData: AuthenticationRequestData) {
        this.invokeStepHandler(ev, additionalData)
    }

    /**
     * Trigger a submit event on the current login form.
     */
    public triggerLoginFormSubmit () {
        this.loginForm.$loginForm.trigger('submit');
    }

    /**
     * Hide the submit button of the current login form.
     */
    public hideSubmitButton() {
        this.loginForm.$submit.removeClass('hidden');
    }

    /**
     * Show the submit button of the current login form.
     */
    public showSubmitButton() {
        this.loginForm.$submit.addClass('hidden');
    }

    /**
     * Invoke the current step handler
     * @param ev
     */
    protected async invokeStepHandler(ev: any, additionalData: AuthenticationRequestData)
    {
        try {
            let requestData: AuthenticationRequestData;

            if (this.isExistingChain()) {
                this.updateCurrentStepType();
                requestData = {...await this.currentStep!.prepareData(), ...additionalData};
            } else {
                requestData = additionalData;
            }

            if (this.loginForm.isDisabled()) {
                return;
            }

            this.loginForm.disableForm();
            const endpoint = this.recoverAccount ? this.recoverAccountEndpoint : (this.isExistingChain() ? this.performAuthenticationEndpoint : this.startAuthenticationEndpoint);
            this.performStep(endpoint, requestData);
        } catch (error: any) {
            this.loginForm.showError(error)
            this.loginForm.enableForm();
        }

    }

    /**
     * Return true if there is an existing authentication chain being traversed
     */
    public isExistingChain(): boolean
    {
        return this.$authenticationStep.attr('rel')!.length > 0;
    }

    /**
     * Clear the error from the login form.
     */
    public clearErrors()
    {
        this.loginForm.clearErrors();
    }
}
