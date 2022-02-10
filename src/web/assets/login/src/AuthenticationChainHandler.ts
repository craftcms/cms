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

        this.prepareForm();
    }

    get $alternatives() { return $('#alternative-types');}
    get $authenticationStep() { return $('#authentication-step');}
    get $restartAuthentication() { return $('#restart-authentication');}
    get $usernameField() { return $('#username-field');}
    get $recoveryButtons() { return $('#recover-account, #cancel-recover');}
    get $authenticationGreeting() { return $('#authentication-greeting');}
    get $recoveryMessage() { return $('#recovery-message');}

    /**
     * Prepare form by cleaning up visibility of some items and attaching relevant event listeners.
     *
     * @protected
     */
    protected prepareForm()
    {
        this.$alternatives.on('click', 'li', (ev) => {
            this.switchStep($(ev.target).attr('rel')!);
        });

        if (this.loginForm.canRememberUser) {
            if (!this.isExistingChain()) {
                this.loginForm.showRememberMe();
            } else {
                this.loginForm.hideRememberMe();
            }
        }

        this.$restartAuthentication.on('click', this.restartAuthentication.bind(this));
        this.$recoveryButtons.on('click', this.toggleRecoverAccountForm.bind(this));
    }

    /**
     * Reset the authentication chain controls and anything related in the login form.
     */
    public resetAuthenticationControls()
    {
        this.$authenticationStep.empty().attr('rel', '');
        this.$authenticationGreeting.remove();
        this.$usernameField.removeClass('hidden');
        this.$recoveryMessage.addClass('hidden');
        this.loginForm.showSubmitButton();
        this.loginForm.showRememberMe();
        this.hideAlternatives();
        this.clearErrors();
        
        if (this.recoverAccount) {
            this.$recoveryButtons.toggleClass('hidden');
            this.recoverAccount = false;
        }
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

        // Let the backend know to let it goooooo.
        Craft.postActionRequest(this.startAuthenticationEndpoint, {});
        if (event) {
            event.preventDefault();
        }
    }

    /**
     * Toggle the account recovery form
     */
    public toggleRecoverAccountForm() {
        this.recoverAccount = !this.recoverAccount;

        this.$recoveryButtons.toggleClass('hidden');

        if (this.recoverAccount) {
            this.$recoveryMessage.removeClass('hidden');
        } else {
            this.$recoveryMessage.addClass('hidden');
        }

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
            this.$usernameField.removeClass('hidden');
            this.$authenticationStep.addClass('hidden');
            this.$alternatives.addClass('hidden');
            stepType?.cleanup();
        } else {
            this.$usernameField.addClass('hidden');
            this.$authenticationStep.removeClass('hidden');
            this.$authenticationStep.attr('rel')!;
            this.$alternatives.removeClass('hidden');
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
                        this.toggleRecoverAccountForm();
                        this.restartAuthentication();
                    }
                }

                // Ensure alternative login options are handled
                if (response.alternatives && Object.keys(response.alternatives).length > 0) {
                    this.showAlternatives(response.alternatives);
                } else {
                    this.hideAlternatives();
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
                    this.prepareForm();
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
        this.$alternatives.removeClass('hidden');
        const $ul = this.$alternatives.find('ul').empty();

        for (const [stepType, description] of Object.entries(alternatives)) {
            $ul.append($(`<li rel="${stepType}">${description}</li>`));
        }
    }

    /**
     * Hide the alternative authentication methods.
     */
    public hideAlternatives()
    {
        this.$alternatives.addClass('hidden');
        this.$alternatives.find('ul').empty();
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
