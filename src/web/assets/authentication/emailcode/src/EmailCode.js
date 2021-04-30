"use strict";
class EmailCode {
    constructor() {
        this.$verificationCode = $('#verificationCode');
        this.validateOnInput = false;
        Craft.LoginForm.registerStepHandler(this.prepareData.bind(this));
    }
    validate() {
        const verificationCode = this.$verificationCode.val();
        if (verificationCode.length === 0) {
            return Craft.t('app', 'Please enter a verification code');
        }
        return true;
    }
    onInput(ev) {
        if (this.validateOnInput && this.validate() === true) {
            Craft.LoginForm.clearErrors();
        }
    }
    /**
     * Prepare the request data.
     *
     * @param ev
     */
    prepareData(ev) {
        const error = this.validate();
        if (error !== true) {
            this.validateOnInput = true;
            return error;
        }
        this.validateOnInput = false;
        return {
            "verification-code": this.$verificationCode.val(),
        };
    }
}
new EmailCode();
