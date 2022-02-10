import {AuthenticationChainHandler} from "./AuthenticationChainHandler";

export class LoginForm
{
    private disabled = false;

    constructor()
    {
        Craft.AuthenticationChainHandler = new AuthenticationChainHandler(this, () =>
            ({
                rememberMe: this.$rememberMe.find('input').prop('checked'),
            })
        );

        if (this.$pendingSpinner.length) {
           this.$loginForm.trigger('submit');
        }
    }

    get $loginForm() {return $('#login-form');}
    get $errors() {return $('#login-errors');}
    get $messages() {return $('#login-messages');}
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
        Craft.appendFootHtml(htmlContent);
    }
}
