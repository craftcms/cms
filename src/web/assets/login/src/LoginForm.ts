type AuthenticationStepHandler = (ev: any) => AuthenticationRequest | string;

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
    footHtml?: string
    stepComplete?: boolean
    alternatives?: AuthenticationAlternatives
}

class LoginForm
{
   readonly switchEndpoint = 'authentication/switch-authentication-step';

    readonly $loginForm = $('#login-form');
    readonly $errors = $('#login-errors');
    readonly $messages = $('#login-messages');
    readonly $spinner = $('#spinner');
    readonly $pendingSpinner = $('#spinner-pending');
    readonly $submit = $('#submit');
    readonly $rememberMeCheckbox = $('#rememberMe');
    readonly $cancelRecover = $('#cancel-recover');
    readonly $recoverAccount = $('#recover-account');
    readonly $alternatives = $('#alternatives');

    private endpoints: {
        [key:string]: string
    } = {};

    constructor($chainContainers: JQuery)
    {
        for (const container of $chainContainers) {
            this.endpoints[container.id] = $(container).data('endpoint');
        }

        this.$loginForm.on('submit', this.invokeStepHandler.bind(this));

        if (this.$pendingSpinner.length) {
            this.$loginForm.trigger('submit');
        }

        this.$recoverAccount.on('click', this.switchForm.bind(this));
        this.$cancelRecover.on('click', this.switchForm.bind(this));

        this.$alternatives.on('click', 'li', (ev) => {
            this.switchStep($(ev.target).attr('rel')!);
        });
    }

    /**
     * Perform the authentication step against the endpoint.
     *
     * @param request
     * @param cb
     */
    public performStep(request: AuthenticationRequest): void
    {
        if (this.$rememberMeCheckbox.prop('checked')) {
            request.rememberMe = true;
        }

        this.clearMessages();
        this.clearErrors();

        Craft.postActionRequest(this.getActiveContainer().data('endpoint'), request, this.processResponse.bind(this));
    }

    /**
     * Switch the current authentication step to an alternative.
     *
     * @param stepType
     */
    public switchStep(stepType: string)
    {
        Craft.postActionRequest(this.getActiveContainer().data('endpoint'), {stepType: stepType, switch: true}, this.processResponse.bind(this));
    }

    /**
     * Process authentication response.
     * @param response
     * @param textStatus
     * @protected
     */
    protected processResponse (response: AuthenticationResponse, textStatus: string)
    {
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
                    this.getActiveContainer().html(response.html)
                }

                if (response.alternatives && Object.keys(response.alternatives).length > 0) {
                    this.showAlternatives(response.alternatives);
                } else {
                    this.hideAlternatives();
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

                // Just in case this was the first step, remove all the misc things.
                if (response.stepComplete) {
                    this.$rememberMeCheckbox.parent().remove();
                    this.$cancelRecover.remove();
                    this.$recoverAccount.remove();
                }
            }
        }

        this.enableForm();
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

    /**
     * Invoke the current step handler bound to the authentication container
     * @param ev
     */
    protected invokeStepHandler(ev: any): boolean
    {
        const handler = this.getActiveContainer().data('handler');

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
        this.$cancelRecover.toggleClass('hidden');
        this.$recoverAccount.toggleClass('hidden');

        for (const containerId of Object.keys(this.endpoints)) {
            $('#' + containerId).toggleClass('hidden');
        }
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

    protected getActiveContainer(): JQuery
    {
        return $('.authentication-chain').not('.hidden');
    }
}
