"use strict";
class LoginForm {
    constructor($chainContainers) {
        this.switchEndpoint = 'authentication/switch-authentication-step';
        this.$loginForm = $('#login-form');
        this.$errors = $('#login-errors');
        this.$messages = $('#login-messages');
        this.$spinner = $('#spinner');
        this.$pendingSpinner = $('#spinner-pending');
        this.$submit = $('#submit');
        this.$rememberMeCheckbox = $('#rememberMe');
        this.$cancelRecover = $('#cancel-recover');
        this.$recoverAccount = $('#recover-account');
        this.$alternatives = $('#alternatives');
        this.endpoints = {};
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
            this.switchStep($(ev.target).attr('rel'));
        });
    }
    /**
     * Perform the authentication step against the endpoint.
     *
     * @param request
     * @param cb
     */
    performStep(request) {
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
    switchStep(stepType) {
        Craft.postActionRequest(this.getActiveContainer().data('endpoint'), { stepType: stepType, switch: true }, this.processResponse.bind(this));
    }
    /**
     * Process authentication response.
     * @param response
     * @param textStatus
     * @protected
     */
    processResponse(response, textStatus) {
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
                    this.getActiveContainer().html(response.html);
                }
                if (response.alternatives) {
                    this.showAlternatives(response.alternatives);
                }
                else {
                    this.hideAlternatives();
                }
                if (response.footHtml) {
                    const jsFiles = response.footHtml.match(/([^"']+\.js)/gm);
                    // For some reason, Chrome will fail to load sourcemap properly when jQuery append is used
                    // So roll our own JS file append-thing.
                    if (jsFiles) {
                        for (const jsFile of jsFiles) {
                            let node = document.createElement('script');
                            node.setAttribute('src', jsFile);
                            document.body.appendChild(node);
                        }
                        // If that fails, use Craft's thing.
                    }
                    else {
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
    showAlternatives(alternatives) {
        this.$alternatives.removeClass('hidden');
        const $ul = this.$alternatives.find('ul').empty();
        for (const [stepType, description] of Object.entries(alternatives)) {
            $ul.append($(`<li rel="${stepType}">${description}</li>`));
        }
    }
    hideAlternatives() {
        this.$alternatives.addClass('hidden');
        this.$alternatives.find('ul').empty();
    }
    /**
     * Invoke the current step handler bound to the authentication container
     * @param ev
     */
    invokeStepHandler(ev) {
        const handler = this.getActiveContainer().data('handler');
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
    getActiveContainer() {
        return $('.authentication-chain').not('.hidden');
    }
}
