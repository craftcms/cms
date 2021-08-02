"use strict";
class EmailStep extends AuthenticationStep {
    constructor() {
        super('craft\\authentication\\type\\Email');
    }
    get $inputField() { return $('#email'); }
    init() {
        this.$inputField.on('input', this.onInput.bind(this));
    }
    cleanup() {
        this.$inputField.off('input', this.onInput.bind(this));
    }
    validate() {
        const emailAddress = this.$inputField.val();
        if (emailAddress.length === 0) {
            return Craft.t('app', 'Please enter a valid email address');
        }
        return true;
    }
    returnFormData() {
        return {
            "email": this.$inputField.val(),
        };
    }
}
new EmailStep();
