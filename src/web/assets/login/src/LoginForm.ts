import {AuthenticationChainHandler} from "./AuthenticationChainHandler";

export class LoginForm
{
    private disabled = false;

    get $loginForm() {return $('#login-form');}
    get $errors() {return $('#login-errors');}
    get $messages() {return $('#login-messages');}
    get $pendingSpinner() {return $('#spinner-pending');}
    get $submit() {return $('#submit');}
    get $rememberMe() { return $('#remember-me-container');}
    get $username() {return $('#username-field input');}
    get $cancelRecover() {return $('#cancel-recover');}
    get $recoverAccount() {return $('#recover-account');}

    get $alternatives() { return $('#alternative-types');}
    get $restartAuthentication() { return $('#restart-authentication');}
    get $usernameField() { return $('#username-field');}
    get $recoveryButtons() { return $('#recover-account, #cancel-recover');}
    get $authenticationGreeting() { return $('#authentication-greeting');}
    get $recoveryMessage() { return $('#recovery-message');}

    get canRememberUser() { return this.$loginForm.data('can-remember'); }

    constructor()
    {
        Craft.AuthenticationChainHandler = new AuthenticationChainHandler(this, () =>
            ({
                rememberMe: this.$rememberMe.find('input').prop('checked'),
            })
        );

        this.prepareForm();

        if (this.$pendingSpinner.length) {
           this.$loginForm.trigger('submit');
        }
    }

    /**
     * Prepare form by cleaning up visibility of some items and attaching relevant event listeners.
     *
     * @protected
     */
    public prepareForm()
    {
        this.$alternatives.on('click', 'li', (ev) => {
            Craft.AuthenticationChainHandler.switchStep($(ev.target).attr('rel')!);
        });

        if (this.canRememberUser) {
            if (!Craft.AuthenticationChainHandler.isExistingChain()) {
                this.showRememberMe();
            } else {
                this.hideRememberMe();
            }
        }

        this.$restartAuthentication.on('click', Craft.AuthenticationChainHandler.restartAuthentication.bind(Craft.AuthenticationChainHandler));
        this.$recoveryButtons.on('click', () => {
            Craft.AuthenticationChainHandler.toggleRecoverAccount();
            this.toggleRecoverAccountForm();

        });
    }


    /**
     * Reset the authentication chain controls and anything related in the login form.
     */
    public resetLoginForm()
    {
        this.$authenticationGreeting.remove();
        this.$usernameField.removeClass('hidden');
        this.$recoveryMessage.addClass('hidden');
        this.showSubmitButton();
        this.showRememberMe();
        this.hideAlternatives();
        this.clearErrors();

        if (Craft.AuthenticationChainHandler.isRecoveringAccount()) {
            this.$recoveryButtons.toggleClass('hidden');
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
     * Show the alternative authentication methods available.
     *
     * @param $alternatives
     */
    public showAlternatives(alternativesHtml: string)
    {
        this.$alternatives.removeClass('hidden').find('ul').empty().append($(alternativesHtml))
    }

    /**
     * Toggle the account recovery form
     */
    public toggleRecoverAccountForm() {
        const recoverAccount = Craft.AuthenticationChainHandler.isRecoveringAccount();

        this.$recoveryButtons.toggleClass('hidden');

        if (recoverAccount) {
            this.$recoveryMessage.removeClass('hidden');
        } else {
            this.$recoveryMessage.addClass('hidden');
        }

        // Presumably, the login name input is shown already.
        if (!Craft.AuthenticationChainHandler.isExistingChain()) {
            return;
        }

        if (recoverAccount) {
            this.$usernameField.removeClass('hidden');
            this.$alternatives.addClass('hidden');
        } else {
            this.$usernameField.addClass('hidden');
            this.$alternatives.removeClass('hidden');
        }
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
        this.$submit.removeClass('loading');
        this.$loginForm.fadeTo(100, 1);
        this.disabled = false;
    }

    public disableForm(): void
    {
        this.$submit.removeClass('active');
        this.$loginForm.fadeTo(100, 0.2, () => this.$submit.addClass('loading'));
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

export function addContainedJsFilesToPage(htmlContent: string) {
    const jsFiles = htmlContent.match(/([^"']+\.js)/gm);
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
        Craft.appendBodyHtml(htmlContent);
    }
}
