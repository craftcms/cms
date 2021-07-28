"use strict";
class VerificationCode extends AuthenticationStep {
    constructor(stepType) {
        super(stepType);
        this.inputSelector = '#verificationCode';
        this.$loginForm.on('input', this.inputSelector, this.onInput.bind(this));
    }
    validate() {
        const verificationCode = this.getVerificationCodeInput().val();
        if (verificationCode.length === 0) {
            return Craft.t('app', 'Please enter a verification code');
        }
        return true;
    }
    returnFormData() {
        return {
            "verification-code": this.getVerificationCodeInput().val()
        };
    }
    getVerificationCodeInput() {
        return this.$loginForm.find(this.inputSelector);
    }
}
