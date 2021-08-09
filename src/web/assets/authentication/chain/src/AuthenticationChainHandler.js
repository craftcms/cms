"use strict";
class AuthenticationChainHandler {
    constructor(loginForm) {
        this.performAuthenticationEndpoint = 'authentication/perform-authentication';
        this.startAuthenticationEndpoint = 'authentication/start-authentication';
        this.recoverAccountEndpoint = 'users/send-password-reset-email';
        this.recoverAccount = false;
        this.authenticationSteps = {};
        this.loginForm = loginForm;
        this.attachListeners();
    }
    get $alternatives() { return $('#alternative-types'); }
    get $authenticationStep() { return $('#authentication-step'); }
    get $restartAuthentication() { return $('#restart-authentication'); }
    get $usernameField() { return $('#username-field'); }
    get $recoveryButtons() { return $('#recover-account, #cancel-recover'); }
    get $authenticationGreeting() { return $('#authentication-greeting'); }
    get $toggleRecover() { return $('.toggle-recover'); }
    /**
     * Attach relevant event listeners.
     *
     * @protected
     */
    attachListeners() {
        this.$alternatives.on('click', 'li', (ev) => {
            this.switchStep($(ev.target).attr('rel'));
        });
        this.$restartAuthentication.on('click', this.restartAuthentication.bind(this));
        this.$recoveryButtons.on('click', this.toggleRecoverAccountForm.bind(this));
    }
    /**
     * Reset the authentication chain controls and anything related in the login form.
     */
    resetAuthenticationControls() {
        this.$authenticationStep.empty().attr('rel', '');
        this.$authenticationGreeting.remove();
        this.$usernameField.removeClass('hidden');
        this.loginForm.$rememberMeCheckbox.parents('.field').removeClass('hidden');
        this.loginForm.$submit.removeClass('hidden');
        this.hideAlternatives();
        this.clearErrors();
    }
    /**
     * Register an authentication step
     *
     * @param stepType
     * @param step
     */
    registerAuthenticationStep(stepType, step) {
        this.authenticationSteps[stepType] = step;
    }
    /**
     * Restart authentication from scratch.
     * @param event
     */
    restartAuthentication(event) {
        this.resetAuthenticationControls();
        if (event) {
            event.preventDefault();
        }
    }
    /**
     * Toggle the account recovery form
     */
    toggleRecoverAccountForm() {
        this.recoverAccount = !this.recoverAccount;
        if (this.recoverAccount) {
            this.$usernameField.removeClass('hidden');
            this.loginForm.$submit.removeClass('hidden');
            this.$authenticationStep.addClass('hidden');
        }
        else {
            this.$usernameField.addClass('hidden');
            this.loginForm.$submit.addClass('hidden');
            this.$authenticationStep.removeClass('hidden');
            this.$authenticationStep.attr('rel');
            if (this.$authenticationStep.attr('rel').length > 0) {
                const stepType = this.authenticationSteps[this.$authenticationStep.attr('rel')];
                stepType.init();
            }
        }
    }
    /**
     * Perform the authentication step against the endpoint.
     *
     * @param {string} endpoint
     * @param {AuthenticationRequest} request
     */
    performStep(endpoint, request) {
        Craft.postActionRequest(endpoint, request, this.processResponse.bind(this));
    }
    /**
     * Switch the current authentication step to an alternative.
     *
     * @param stepType
     */
    switchStep(stepType) {
        if (this.loginForm.isDisabled()) {
            return;
        }
        this.loginForm.disableForm();
        Craft.postActionRequest(this.performAuthenticationEndpoint, {
            stepType: stepType,
            switch: true
        }, this.processResponse.bind(this));
    }
    //     Garnish.Modal.extend({
    //                              init: function() {
    //     var $container = $('<div class="modal fitted email-sent"><div class="body">' + Craft.t('app', 'Check your email for instructions to reset your password.') + '</div></div>')
    //     .appendTo(Garnish.$bod);
    //
    //     this.base($container);
    // },
    //
    // hide: function() {
    // }
    // });
    /**
     * Process authentication response.
     * @param response
     * @param textStatus
     * @protected
     */
    processResponse(response, textStatus) {
        var _a, _b, _c;
        if (textStatus == 'success') {
            if (response.success && ((_a = response.returnUrl) === null || _a === void 0 ? void 0 : _a.length)) {
                window.location.href = response.returnUrl;
                // Keep the form disabled
                return;
            }
            else {
                // Take not of errors and messages
                if (response.error) {
                    this.loginForm.showError(response.error);
                    Garnish.shake(this.loginForm.$loginForm);
                }
                if (response.message) {
                    this.loginForm.showMessage(response.message);
                }
                // Handle password reset response early and bail
                if (response.passwordReset) {
                    if (!response.error) {
                        this.toggleRecoverAccountForm();
                        this.restartAuthentication();
                    }
                }
                // Ensure alternative login options are handled
                if (response.alternatives && Object.keys(response.alternatives).length > 0) {
                    this.showAlternatives(response.alternatives);
                }
                else {
                    this.hideAlternatives();
                }
                // Keep track of current step type
                if (response.stepType) {
                    this.$authenticationStep.attr('rel', response.stepType);
                }
                // Load any JS files if needed
                if (response.footHtml) {
                    const jsFiles = response.footHtml.match(/([^"']+\.js)/gm);
                    const existingSources = Array.from(document.scripts).map(node => node.getAttribute('src')).filter(val => val && val.length > 0);
                    // For some reason, Chrome will fail to load sourcemap properly when jQuery append is used
                    // So roll our own JS file append-thing.
                    if (jsFiles) {
                        for (const jsFile of jsFiles) {
                            if (!existingSources.includes(jsFile)) {
                                let node = document.createElement('script');
                                node.setAttribute('src', jsFile);
                                document.body.appendChild(node);
                            }
                        }
                        // If that fails, use Craft's thing.
                    }
                    else {
                        Craft.appendFootHtml(response.footHtml);
                    }
                }
                const initStepType = (stepType) => {
                    if (this.authenticationSteps[stepType]) {
                        this.authenticationSteps[stepType].init();
                    }
                };
                // Display the HTML
                if (response.html) {
                    (_b = this.currentStep) === null || _b === void 0 ? void 0 : _b.cleanup();
                    this.$authenticationStep.html(response.html);
                    initStepType(response.stepType);
                }
                // Display the HTML
                if (response.loginFormHtml) {
                    (_c = this.currentStep) === null || _c === void 0 ? void 0 : _c.cleanup();
                    this.loginForm.$loginForm.html(response.loginFormHtml);
                    this.attachListeners();
                    initStepType(response.stepType);
                }
                // Just in case this was the first step, remove all the misc things.
                if (response.stepComplete) {
                    this.loginForm.$rememberMeCheckbox.parents('.field').addClass('hidden');
                }
            }
        }
        this.loginForm.enableForm();
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
    handleFormSubmit(ev, additionalData) {
        this.invokeStepHandler(ev, additionalData);
    }
    /**
     * Invoke the current step handler bound to the authentication container
     * @param ev
     */
    async invokeStepHandler(ev, additionalData) {
        try {
            let requestData;
            if (this.isExistingChain()) {
                const stepType = this.$authenticationStep.attr('rel');
                const stepHandler = this.authenticationSteps[stepType];
                requestData = Object.assign(Object.assign({}, await stepHandler.prepareData()), additionalData);
                this.currentStep = stepHandler;
            }
            else {
                requestData = additionalData;
            }
            if (this.loginForm.isDisabled()) {
                return;
            }
            this.loginForm.disableForm();
            const endpoint = this.recoverAccount ? this.recoverAccountEndpoint : (this.isExistingChain() ? this.performAuthenticationEndpoint : this.startAuthenticationEndpoint);
            this.performStep(endpoint, requestData);
        }
        catch (error) {
            this.loginForm.showError(error);
            this.loginForm.enableForm();
        }
    }
    isExistingChain() {
        return this.$authenticationStep.attr('rel').length > 0;
    }
    clearErrors() {
        this.loginForm.clearErrors();
    }
}
