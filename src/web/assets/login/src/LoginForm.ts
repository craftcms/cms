type AuthenticationStepHandler = (ev: any) => AuthenticationRequest | string;

type AuthenticationRequest = {
    [key: string]: any
}

type AuthenticationResponse = {
    returnUrl?: string
    success?: boolean
    error?: string
    message?: string
    html?: string
    footHtml?: string
    stepComplete?: boolean
}

class LoginForm
{
    readonly authenticationEndpoint = 'authentication/perform-authentication';
    readonly recoverEndpoint = 'authentication/recover-account';
    readonly $loginForm = $('#login-form');
    readonly $authContainer = $('#authentication-container');
    readonly $recoverContainer = $('#recovery-container');
    readonly $errors = $('#login-errors');
    readonly $messages = $('#login-messages');
    readonly $spinner = $('#spinner');
    readonly $pendingSpinner = $('#spinner-pending');
    readonly $submit = $('#submit');
    readonly $rememberMeCheckbox = $('#rememberMe');
    readonly $cancelRecover = $('#cancel-recover');
    readonly $recoverAccount = $('#recover-account');

    /**
     * The authentication step handler function.
     *
     * @private
     */
    private authenticationStepHandler?: AuthenticationStepHandler;

    /**
     * The recovery authentication step handler function.
     *
     * @private
     */
    private recoveryStepHandler?: AuthenticationStepHandler;

    /**
     * Whether currently the account recovery form is shown.
     *
     * @private
     */
    private showingRecoverForm = false;

    constructor()
    {
        this.$loginForm.on('submit', this.invokeStepHandler.bind(this));

        if (this.$pendingSpinner.length) {
            this.$loginForm.trigger('submit');
        }


        this.$recoverAccount.on('click', this.switchForm.bind(this));
        this.$cancelRecover.on('click', this.switchForm.bind(this));
    }

    /**
     * Perform the authentication step against the endpoint.
     *
     * @param request
     * @param cb
     */
    public performStep(request: AuthenticationRequest): void
    {
        request.scenario = Craft.cpLoginChain;

        if (this.$rememberMeCheckbox.prop('checked')) {
            request.rememberMe = true;
        }

        this.clearMessages();
        this.clearErrors();

        let container: JQuery;
        let handler: "recoveryStepHandler" | "authenticationStepHandler";
        let endpoint: string;

        if (this.showingRecoverForm) {
            endpoint = this.recoverEndpoint;
            container = this.$recoverContainer;
            handler = "recoveryStepHandler";
        } else {
            endpoint = this.authenticationEndpoint;
            container = this.$authContainer;
            handler = "authenticationStepHandler";
        }

        Craft.postActionRequest(endpoint, request, (response: AuthenticationResponse, textStatus: string) => {

            if (textStatus == 'success') {
                if (response.success && response.returnUrl?.length) {
                    window.location.href = response.returnUrl;
                } else {
                    if (response.error) {
                        this.showError(response.error);
                        Garnish.shake(this.$loginForm);
                    }

                    if (response.message) {
                        this.showMessage(response.message);
                    }

                    if (response.html) {
                        container.html(response.html)
                        this[handler] = undefined;
                    }

                    if (response.footHtml) {
                        const jsFiles = response.footHtml.match(/([^"']+\.js)/gm);
                        
                        // For some reason, Chrome will fail to load sourcemap properly when jQuery append is used
                        // So roll our own JS file append-thing.
                        if (jsFiles) {
                            for (const jsFile of jsFiles) {
                                let node = document.createElement('script');
                                node.setAttribute('src', jsFile)
                                document.body.appendChild(node);
                            }
                        // If that fails, use Craft's thing.
                        } else {
                            Craft.appendFootHtml(response.footHtml);
                        }
                    }

                    // Just in case this was the first step, remove all the misc things.
                    if (response.stepComplete) {
                        this.$rememberMeCheckbox.parent().remove();
                        this.$cancelRecover.remove();
                        this.$recoverAccount.remove();
                    }
                }
            }

            this.enableForm();
        });
    }

    /**
     * Show an error.
     *
     * @param error
     */
    public showError(error: string)
    {
        this.clearErrors();
        $('<p style="display: none;">' + error + '</p>')
            .appendTo(this.$errors)
            // @ts-ignore
            .velocity('fadeIn');
    }

    /**
     * Show a message.
     *
     * @param message
     */
    public showMessage(message: string)
    {
        this.clearMessages();
        $('<p style="display: none;">' + message + '</p>')
            .appendTo(this.$messages)
            // @ts-ignore
            .velocity('fadeIn');
    }

    /**
     * Register an authentication step handler function that performs validation and data preparation.
     * It must return either a hash of data to be submitted for authentication or a string which is then interpreted as an error message.
     * If an empty string is returned, no action is taken.
     *
     * @param handler
     * @param isRecoveryStep whether this is an account recovery step handler.
     */
    public registerStepHandler(handler: AuthenticationStepHandler, isRecoveryStep = false): void
    {
        if (isRecoveryStep) {
            this.recoveryStepHandler = handler;
        } else {
            this.authenticationStepHandler = handler;
        }
    }

    /**
     * Invoke the current authentication or recovery step handler.
     * @param ev
     */
    protected invokeStepHandler(ev: any): boolean
    {
        const handler = this.showingRecoverForm ? this.recoveryStepHandler : this.authenticationStepHandler;

        if (typeof handler == "function") {
            const data = handler(ev);
            if (typeof data == "object") {
                this.disableForm();
                this.performStep(data);
            } else {
                this.showError(data);
            }
        } else {
            this.performStep({});
        }

        return false;
    }

    /**
     * Clear all the errors.
     *
     * @protected
     */
    public clearErrors()
    {
        this.$errors.empty();
    }

    /**
     * Switch the displayed form between authentication and recovery.
     *
     * @protected
     */
    protected switchForm()
    {
        this.$authContainer.toggleClass('hidden');
        this.$recoverContainer.toggleClass('hidden');
        this.$cancelRecover.toggleClass('hidden');
        this.$recoverAccount.toggleClass('hidden');

        this.showingRecoverForm = !this.showingRecoverForm;
    }

    /**
     * Clear all the messages.
     *
     * @protected
     */
    protected clearMessages()
    {
        this.$messages.empty();
    }

    protected enableForm(): void
    {
        this.$submit.addClass('active');
        this.$spinner.addClass('hidden');
    }

    protected disableForm(): void
    {
        this.$submit.removeClass('active');
        this.$spinner.removeClass('hidden');
    }
}

Craft.LoginForm = new LoginForm();
