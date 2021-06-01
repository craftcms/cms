"use strict";
class AuthenticatorFormHandler {
    constructor() {
        this.endpoint = 'authentication/update-authenticator-settings';
        this.disabled = false;
        this.$container = $('#authenticator-settings');
        this.attachEvents();
    }
    /**
     * Attach the listeners for field events.
     *
     * @private
     */
    attachEvents() {
        $('.authenticator-field').on('keydown', (event) => {
            if (event.key == "Enter") {
                event.stopImmediatePropagation();
                this.handleAuthenticatorUpdate();
                return false;
            }
        });
        $('#update-authenticator').on('click', (event) => {
            event.stopImmediatePropagation();
            this.handleAuthenticatorUpdate();
        });
    }
    /**
     * Handle authenticator setting update.
     *
     * @protected
     */
    handleAuthenticatorUpdate() {
        if (Craft.elevatedSessionManager.fetchingTimeout) {
            return;
        }
        const $detach = $('.authenticator-field.detach');
        const $verificationCode1 = $('#verification-code-1');
        const $verificationCode2 = $('#verification-code-2');
        // If detaching
        if ($detach.length > 0) {
            if ($detach.val().length > 0) {
                if ($detach.val() !== 'detach') {
                    Garnish.shake($detach);
                }
                else {
                    Craft.elevatedSessionManager.requireElevatedSession(this.submitAuthenticatorUpdate.bind(this));
                }
            }
        }
        else {
            if ($verificationCode1.val().length == 0 || $verificationCode2.val().length == 0) {
                return;
            }
            Craft.elevatedSessionManager.requireElevatedSession(this.submitAuthenticatorUpdate.bind(this));
        }
    }
    /**
     * Submit authenticator setting update.
     * @protected
     */
    submitAuthenticatorUpdate() {
        if (this.disabled) {
            return;
        }
        this.disable();
        const $fields = $('input.authenticator-field');
        let data = {};
        for (const field of $fields) {
            data[field.getAttribute('name')] = field.value;
        }
        Craft.postActionRequest(this.endpoint, data, (response, textStatus) => {
            this.enable();
            if (response.message) {
                alert(response.message);
            }
            if (response.html) {
                this.$container.replaceWith(response.html);
                this.$container = $('#authenticator-settings');
                this.attachEvents();
            }
        });
    }
    /**
     * Disable the setting fields.
     *
     * @protected
     */
    disable() {
        this.disabled = true;
        this.$container.fadeTo(100, 0.5);
    }
    /**
     * Enable the setting fields.
     *
     * @protected
     */
    enable() {
        this.disabled = false;
        this.$container.fadeTo(100, 1);
    }
}
new AuthenticatorFormHandler();
