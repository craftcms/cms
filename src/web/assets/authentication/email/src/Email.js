"use strict";
class Email extends AuthenticationStep {
    constructor() {
        super('craft\\authentication\\type\\Email');
        this.inputSelector = '#email';
        this.$loginForm.on('input', this.inputSelector, this.onInput.bind(this));
    }
    validate() {
        const emailAddress = this.getEmailInput().val();
        if (emailAddress.length === 0) {
            return Craft.t('app', 'Please enter a valid email address');
        }
        return true;
    }
    returnFormData() {
        return {
            "email": this.getEmailInput().val(),
        };
    }
    getEmailInput() {
        return this.$loginForm.find(this.inputSelector);
    }
}
new Email();
