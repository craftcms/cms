type AuthenticationStepHandler = () => AuthenticationRequest;

type AuthenticationRequest = {
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
    alternatives?: AuthenticationAlternatives
}

class AuthenticationChainHandler
{
    readonly performAuthenticationEndpoint = 'authentication/perform-authentication';
    readonly startAuthenticationEndpoint = 'authentication/start-authentication';
    readonly loginForm: LoginForm;

    private currentStep?: AuthenticationStep;

    private authenticationSteps: {
        [key: string]: AuthenticationStep
    } = {};

    public constructor(loginForm: LoginForm)
    {
        this.loginForm = loginForm;
        this.attachListeners();
    }

    get $alternatives() { return $('#alternative-types');}
    get $authenticationStep() { return $('#authentication-step');}
    get $restartAuthentication() { return $('#restart-authentication');}
    get $startAuthentication() { return $('#start-authentication');}
    get $authenticationGreeting() { return $('#authentication-greeting');}

    /**
     * Attach relevant event listeners.
     *
     * @protected
     */
    protected attachListeners()
    {
        this.$alternatives.on('click', 'li', (ev) => {
            this.switchStep($(ev.target).attr('rel')!);
        });

        this.$restartAuthentication.on('click', (event) => {
            this.resetAuthenticationControls();
            event.preventDefault();
        });
    }

    /**
     * Reset the authentication chain controls and anything related in the login form.
     */
    public resetAuthenticationControls()
    {
        this.$authenticationStep.empty().attr('rel', '');
        this.$authenticationGreeting.remove();
        this.$startAuthentication.removeClass('hidden');
        this.$startAuthentication.removeClass('hidden');
        this.loginForm.$submit.removeClass('hidden');
        this.clearErrors();
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

        Craft.postActionRequest(this.performAuthenticationEndpoint, {
            stepType: stepType,
            switch: true
        }, this.processResponse.bind(this));
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
                if (response.error) {
                    this.loginForm.showError(response.error);
                    Garnish.shake(this.loginForm.$loginForm);
                }

                if (response.message) {
                    this.loginForm.showMessage(response.message);
                }

                if (response.alternatives && Object.keys(response.alternatives).length > 0) {
                    this.showAlternatives(response.alternatives);
                } else {
                    this.hideAlternatives();
                }

                if (response.stepType){
                    this.$authenticationStep.attr('rel', response.stepType);
                }

                if (response.footHtml) {
                    const jsFiles = response.footHtml.match(/([^"']+\.js)/gm);

                    const existingSources = Array.from(document.scripts).map(node => node.getAttribute('src')).filter(val => val && val.length > 0);

                    // For some reason, Chrome will fail to load sourcemap properly when jQuery append is used
                    // So roll our own JS file append-thing.
                    if (jsFiles) {
                        for (const jsFile of jsFiles) {
                            if (!existingSources.includes(jsFile)) {
                                let node = document.createElement('script');
                                node.setAttribute('src', jsFile)
                                document.body.appendChild(node);
                            }
                        }
                        // If that fails, use Craft's thing.
                    } else {
                        Craft.appendFootHtml(response.footHtml);
                    }
                }

                const initStepType = (stepType: string) => {
                    if (this.authenticationSteps[stepType]) {
                        this.authenticationSteps[stepType].init();
                    }
                }

                if (response.html) {
                    this.currentStep?.cleanup();
                    this.$authenticationStep.html(response.html);
                    initStepType(response.stepType!);
                }

                if (response.loginFormHtml) {
                    this.currentStep?.cleanup();
                    this.loginForm.$loginForm.html(response.loginFormHtml);
                    this.attachListeners();
                    initStepType(response.stepType!);
                }

                // Just in case this was the first step, remove all the misc things.
                if (response.stepComplete) {
                    this.loginForm.$rememberMeCheckbox.parent().remove();
                }
            }
        }

        this.loginForm.enableForm();
    }

    public showAlternatives(alternatives: AuthenticationAlternatives)
    {
        this.$alternatives.removeClass('hidden');
        const $ul = this.$alternatives.find('ul').empty();

        for (const [stepType, description] of Object.entries(alternatives)) {
            $ul.append($(`<li rel="${stepType}">${description}</li>`));
        }
    }

    public hideAlternatives()
    {
        this.$alternatives.addClass('hidden');
        this.$alternatives.find('ul').empty();
    }

    public handleFormSubmit (ev: any, additionalData: AuthenticationRequest) {
        this.invokeStepHandler(ev, additionalData)
    }

    /**
     * Invoke the current step handler bound to the authentication container
     * @param ev
     */
    protected async invokeStepHandler(ev: any, additionalData: AuthenticationRequest)
    {
        try {
            let requestData: AuthenticationRequest;

            if (this.isExistingChain()) {
                const stepType = this.$authenticationStep.attr('rel')!;
                const stepHandler = this.authenticationSteps[stepType];
                requestData = {...await stepHandler.prepareData(), ...additionalData};
                this.currentStep = stepHandler;
            } else {
                requestData = additionalData;
            }

            if (this.loginForm.isDisabled()) {
                return;
            }

            this.loginForm.disableForm();
            this.performStep(this.isExistingChain() ? this.performAuthenticationEndpoint : this.startAuthenticationEndpoint, requestData);
        } catch (error) {
            this.loginForm.showError(error)
            this.loginForm.enableForm();
        }

    }

    public isExistingChain(): boolean
    {
        return this.$authenticationStep.attr('rel')!.length > 0;
    }

    public clearErrors()
    {
        this.loginForm.clearErrors();
    }
}
