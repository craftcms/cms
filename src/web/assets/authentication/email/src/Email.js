"use strict";
class Email {
    constructor() {
        this.$email = $('#email');
        this.validateOnInput = false;
        Craft.LoginForm.registerStepHandler(this.prepareData.bind(this), this.$email.parents('#recovery-container').length > 0);
    }
    validate() {
        const emailAddress = this.$email.val();
        if (emailAddress.length === 0) {
            return Craft.t('app', 'Please enter a valid email address');
        }
        return true;
    }
    onInput(ev) {
        if (this.validateOnInput && this.validate() === true) {
            Craft.LoginForm.clearErrors();
        }
    }
    prepareData(ev) {
        const error = this.validate();
        if (error !== true) {
            this.validateOnInput = true;
            return error;
        }
        this.validateOnInput = false;
        return {
            "email": this.$email.val(),
        };
    }
}
new Email();
