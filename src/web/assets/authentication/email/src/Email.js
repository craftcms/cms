"use strict";
class Email extends AuthenticationStep {
    constructor() {
        super();
        this.$email = $('#email');
        this.stepType = "craft\\authentication\\type\\Email";
        this.$email.parents('.authentication-chain').data('handler', this.prepareData.bind(this));
        this.$email.on('input', this.onInput.bind(this));
    }
    validate() {
        const emailAddress = this.$email.val();
        if (emailAddress.length === 0) {
            return Craft.t('app', 'Please enter a valid email address');
        }
        return true;
    }
    returnFormData() {
        return {
            "email": this.$email.val(),
        };
    }
}
new Email();
