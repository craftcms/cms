"use strict";
class VerificationCode extends AuthenticationStep {
    constructor(stepType) {
        super(stepType);
    }
    get $verificationCode() { return $('#verificationCode'); }
    init() {
        this.$verificationCode.on('input', this.onInput.bind(this));
    }
    cleanup() {
        this.$verificationCode.off('input', this.onInput.bind(this));
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
            "verification-code": this.$verificationCode.val()
        };
    }
}
