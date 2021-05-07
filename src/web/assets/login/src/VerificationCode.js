"use strict";
class VerificationCode extends AuthenticationStep {
    constructor() {
        super();
        this.$verificationCode = $('#verificationCode');
        this.$verificationCode.parents('.authentication-chain').data('handler', this.prepareData.bind(this));
        this.$verificationCode.on('input', this.onInput.bind(this));
    }
    validate() {
        const verificationCode = this.$verificationCode.val();
        if (verificationCode.length === 0) {
            return Craft.t('app', 'Please enter a verification code');
        }
        return true;
    }
    returnFormData() {
        return {
            "verification-code": this.$verificationCode.val(),
        };
    }
}
