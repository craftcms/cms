type AuthenticationStepHandler = (ev: any) => AuthenticationRequest | string;

type AuthenticationRequest = {
    [key: string]: any
}

type AuthenticationResponse = {
    returnUrl?: string,
    success?: boolean,
    error?: string,
    message?: string,
    html?: string
}

class LoginForm
{
    readonly authenticationTarget = 'authentication/perform-authentication';
    readonly $loginForm = $('#login-form');
    readonly $authContainer = $('#authentication-container');
    readonly $errors = $('#login-errors');
    readonly $messages = $('#messages');
    readonly $spinner = $('#spinner');
    readonly $submit = $('#submit');
    readonly $rememberMeCheckbox = $('#rememberMe');
    readonly $forgotPassword = $('#forgot-password');
    readonly $rememberPassword = $('#remember-password')

    /**
     * The authentication step handler function.
     *
     * @private
     */
    private stepHandler?: AuthenticationStepHandler;

    constructor()
    {
        this.$loginForm.on('submit', this.invokeStepHandler.bind(this));
        // TODO this form must handle "remember me" functionality.
        // this.$forgotPassword.on('click', 'onSwitchForm');
        // this.$rememberPassword.on('click', 'onSwitchForm');
    }

    /**
     * Perform the authentication against the endpoint.
     *
     * @param request
     * @param cb
     */
    public performAuthentication(request: AuthenticationRequest): void
    {
        request.scenario = Craft.cpLoginChain;

        Craft.postActionRequest(this.authenticationTarget, request, (response: AuthenticationResponse, textStatus: string) => {
            this.clearMessages();
            this.clearErrors();

            if (textStatus == 'success') {
                if (response.success) {
                    window.location.href = response.returnUrl as string;
                } else {
                    if (response.error) {
                        this.showError(response.error);
                        Garnish.shake(this.$loginForm);
                    }

                    if (response.message) {
                        this.showMessage(response.message);
                    }

                    if (response.html) {
                        this.$authContainer.html(response.html)
                        this.stepHandler = undefined;
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
     */
    public registerStepHandler(handler: AuthenticationStepHandler): void
    {
        this.stepHandler = handler;
    }

    public invokeStepHandler(ev: any): boolean
    {
        if (typeof this.stepHandler == "function") {
            const data = this.stepHandler(ev);
            if (typeof data == "object") {
                this.disableForm();
                this.performAuthentication(data);
            } else {
                this.showError(data);
            }
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
