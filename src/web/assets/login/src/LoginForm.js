"use strict";
class LoginForm {
    constructor() {
        this.authenticationTarget = 'authentication/perform-authentication';
        this.$loginForm = $('#login-form');
        this.$authContainer = $('#authentication-container');
        this.$errors = $('#login-errors');
        this.$messages = $('#messages');
        this.$spinner = $('#spinner');
        this.$submit = $('#submit');
        this.$rememberMeCheckbox = $('#rememberMe');
        this.$forgotPassword = $('#forgot-password');
        this.$rememberPassword = $('#remember-password');
        this.$loginForm.on('submit', this.invokeStepHandler.bind(this));
        // this.$forgotPassword.on('click', 'onSwitchForm');
        // this.$rememberPassword.on('click', 'onSwitchForm');
    }
    /**
     * Perform the authentication against the endpoint.
     *
     * @param request
     * @param cb
     */
    performAuthentication(request) {
        request.scenario = Craft.cpLoginChain;
        Craft.postActionRequest(this.authenticationTarget, request, (response, textStatus) => {
            this.clearMessages();
            this.clearErrors();
            if (textStatus == 'success') {
                if (response.success) {
                    window.location.href = response.returnUrl;
                }
                else {
                    if (response.error) {
                        this.showError(response.error);
                        Garnish.shake(this.$loginForm);
                    }
                    if (response.message) {
                        this.showMessage(response.message);
                    }
                    if (response.html) {
                        this.$authContainer.html(response.html);
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
    showError(error) {
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
    showMessage(message) {
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
    registerStepHandler(handler) {
        this.stepHandler = handler;
    }
    invokeStepHandler(ev) {
        if (typeof this.stepHandler == "function") {
            const data = this.stepHandler(ev);
            if (typeof data == "object") {
                this.disableForm();
                this.performAuthentication(data);
            }
            else {
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
    clearErrors() {
        this.$errors.empty();
    }
    /**
     * Clear all the messages.
     *
     * @protected
     */
    clearMessages() {
        this.$messages.empty();
    }
    enableForm() {
        this.$submit.addClass('active');
        this.$spinner.addClass('hidden');
    }
    disableForm() {
        this.$submit.removeClass('active');
        this.$spinner.removeClass('hidden');
    }
}
Craft.LoginForm = new LoginForm();
