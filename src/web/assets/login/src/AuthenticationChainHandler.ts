import {addContainedJsFilesToPage, LoginForm} from "./LoginForm";

import ClickEvent = JQuery.ClickEvent;
import {AuthenticationStep} from "./AuthenticationStep";

type AuthenticationStepHandler = () => AuthenticationRequest;

export type AuthenticationRequest = {
    [key: string]: any
}

type AuthenticationAlternatives = {
    [key: string]: any
}

type AuthenticationResponse = {
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
        Craft.postActionRequest(this.startAuthenticationEndpoint, {});

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
     * Perform the authentication step against the endpoint.
     *
     * @param {string} endpoint
     * @param {AuthenticationRequest} request
     */
    public performStep(endpoint: string, request: AuthenticationRequest): void
    {
        Craft.postActionRequest(endpoint, request, this.processResponse.bind(this));
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

        Craft.postActionRequest(this.performAuthenticationEndpoint, {
            alternateStep: stepType,
        }, this.processResponse.bind(this));
    }

    protected updateCurrentStepType()
    {
        this.currentStep = this.authenticationSteps[this.$authenticationStep.attr('rel')!]
    }

    /**
     * Process authentication response.
     * @param response
     * @param textStatus
     * @protected
     */
    protected processResponse(response: AuthenticationResponse, textStatus: string)
    {
        if (textStatus == 'success') {
            if (response.success && response.returnUrl?.length) {
                window.location.href = response.returnUrl;
                // Keep the form disabled
                return;
            } else {
                // Take not of errors and messages
                if (response.error) {
                    this.loginForm.showError(response.error);
                    Garnish.shake(this.loginForm.$loginForm);
                }
                if (response.message) {
                    this.loginForm.showMessage(response.message);
                }

                // Handle password reset response early and bail
                if (response.passwordReset) {
                    if (!response.error) {
                        this.loginForm.toggleRecoverAccountForm();
                        this.restartAuthentication();
                    }
                }

                // Ensure alternative login options are handled
                if (response.alternatives && Object.keys(response.alternatives).length > 0) {
                    this.showAlternatives(response.alternatives);
                } else {
                    this.loginForm.hideAlternatives();
                }

                // Keep track of current step type
                if (response.stepType){
                    this.$authenticationStep.attr('rel', response.stepType);
                }

                // Load any JS files if needed
                if (response.footHtml) {
                    addContainedJsFilesToPage(response.footHtml);
                }

                const initStepType = (stepType: string) => {
                    if (this.authenticationSteps[stepType]) {
                        this.authenticationSteps[stepType].init();
                    }
                }

                // Display the HTML for the auth step.
                if (response.html) {
                    this.currentStep?.cleanup();
                    this.$authenticationStep.html(response.html);
                    initStepType(response.stepType!);
                }

                // Display the HTML for the entire login form, in case we just started an authentication chain
                if (response.loginFormHtml) {
                    this.currentStep?.cleanup();
                    this.loginForm.$loginForm.html(response.loginFormHtml);
                    this.loginForm.prepareForm();
                    initStepType(response.stepType!);
                }

                // Just in case this was the first step, remove all the misc things.
                if (response.stepComplete) {
                    this.loginForm.hideRememberMe();
                }
            }
        }

        this.loginForm.enableForm();
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
    public handleFormSubmit (ev: any, additionalData: AuthenticationRequest) {
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
    protected async invokeStepHandler(ev: any, additionalData: AuthenticationRequest)
    {
        try {
            let requestData: AuthenticationRequest;

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
