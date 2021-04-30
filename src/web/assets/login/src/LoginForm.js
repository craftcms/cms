"use strict";
class LoginForm {
    constructor() {
        this.authenticationEndpoint = 'authentication/perform-authentication';
        this.recoverEndpoint = 'authentication/recover-account';
        this.$loginForm = $('#login-form');
        this.$authContainer = $('#authentication-container');
        this.$recoverContainer = $('#recovery-container');
        this.$errors = $('#login-errors');
        this.$messages = $('#login-messages');
        this.$spinner = $('#spinner');
        this.$pendingSpinner = $('#spinner-pending');
        this.$submit = $('#submit');
        this.$rememberMeCheckbox = $('#rememberMe');
        this.$cancelRecover = $('#cancel-recover');
        this.$recoverAccount = $('#recover-account');
        /**
         * Whether currently the account recovery form is shown.
         *
         * @private
         */
        this.showingRecoverForm = false;
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
    performStep(request) {
        request.scenario = Craft.cpLoginChain;
        if (this.$rememberMeCheckbox.prop('checked')) {
            request.rememberMe = true;
        }
        this.clearMessages();
        this.clearErrors();
        let container;
        let handler;
        let endpoint;
        if (this.showingRecoverForm) {
            endpoint = this.recoverEndpoint;
            container = this.$recoverContainer;
            handler = "recoveryStepHandler";
        }
        else {
            endpoint = this.authenticationEndpoint;
            container = this.$authContainer;
            handler = "authenticationStepHandler";
        }
        Craft.postActionRequest(endpoint, request, (response, textStatus) => {
            var _a;
            if (textStatus == 'success') {
                if (response.success && ((_a = response.returnUrl) === null || _a === void 0 ? void 0 : _a.length)) {
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
                        container.html(response.html);
                        this[handler] = undefined;
                    }
                    if (response.footHtml) {
                        Craft.appendFootHtml(response.footHtml);
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
     * @param isRecoveryStep whether this is an account recovery step handler.
     */
    registerStepHandler(handler, isRecoveryStep = false) {
        if (isRecoveryStep) {
            this.recoveryStepHandler = handler;
        }
        else {
            this.authenticationStepHandler = handler;
        }
    }
    /**
     * Invoke the current authentication or recovery step handler.
     * @param ev
     */
    invokeStepHandler(ev) {
        const handler = this.showingRecoverForm ? this.recoveryStepHandler : this.authenticationStepHandler;
        if (typeof handler == "function") {
            const data = handler(ev);
            if (typeof data == "object") {
                this.disableForm();
                this.performStep(data);
            }
            else {
                this.showError(data);
            }
        }
        else {
            this.performStep({});
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
     * Switch the displayed form between authentication and recovery.
     *
     * @protected
     */
    switchForm() {
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
