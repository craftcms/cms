"use strict";
class EmailCode extends AuthenticationStep {
    constructor() {
        super();
        this.$verificationCode = $('#verificationCode');
        this.stepType = "craft\\authentication\\type\\mfa\\EmailCode";
        const isRecoveryStep = this.$verificationCode.parents('#recovery-container').length > 0;
        Craft.LoginForm.registerStepHandler(this.prepareData.bind(this), isRecoveryStep);
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
new EmailCode();
