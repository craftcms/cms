class LoginForm
{
    private disabled = false;

    constructor()
    {
        // todo allow constructor to pass in other login handlers
        Craft.AuthenticationChainHandler = new AuthenticationChainHandler(this);

        this.$loginForm.on('submit', (event) => {
            this.clearErrors();
            this.clearMessages();

            let additionalData: AuthenticationRequest = {
                rememberMe: this.$rememberMe.find('input').prop('checked'),
            };

            if (!Craft.AuthenticationChainHandler.isExistingChain()) {
                additionalData.loginName = this.$username.val();
            }

            Craft.AuthenticationChainHandler.handleFormSubmit(event, additionalData);

            event.preventDefault();
        });

        if (this.$pendingSpinner.length) {
           this.$loginForm.trigger('submit');
        }
    }

    get $loginForm() {return $('#login-form');}
    get $errors() {return $('#login-errors');}
    get $messages() {return $('#login-messages');}
    get $spinner() {return $('#spinner');}
    get $pendingSpinner() {return $('#spinner-pending');}
    get $submit() {return $('#submit');}
    get $rememberMe() { return $('#remember-me-container');}
    get $username() {return $('#username-field input');}
    get $cancelRecover() {return $('#cancel-recover');}
    get $recoverAccount() {return $('#recover-account');}

    get canRememberUser() { return this.$loginForm.data('can-remember'); }
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
    public clearMessages()
    {
        this.$messages.empty();
    }

    public enableForm(): void
    {
        this.$submit.addClass('active');
        this.$spinner.addClass('hidden');
        this.$loginForm.fadeTo(100, 1);
        this.disabled = false;
    }

    public disableForm(): void
    {
        this.$submit.removeClass('active');
        this.$spinner.removeClass('hidden');
        this.$loginForm.fadeTo(100, 0.2);
        this.disabled = true;
    }

    public isDisabled(): boolean
    {
        return this.disabled;
    }

    public showRememberMe()
    {
        if (this.canRememberUser) {
            this.$loginForm.addClass('remember-me');
            this.$rememberMe.removeClass('hidden');
        }
    }

    public hideRememberMe()
    {
        this.$loginForm.removeClass('remember-me');
        this.$rememberMe.addClass('hidden');
    }

    public showSubmitButton()
    {
        this.$submit.removeClass('hidden');
    }

    public hideSubmitButton()
    {
        this.$submit.addClass('hidden');
    }
}

new LoginForm();
