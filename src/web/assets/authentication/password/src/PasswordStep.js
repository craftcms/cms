"use strict";
class PasswordStep extends AuthenticationStep {
    constructor() {
        super('craft\\authentication\\type\\Password');
        this.passwordSelector = '#password';
    }
    get $passwordField() { return $(this.passwordSelector); }
    init() {
        this.passwordInput = new Craft.PasswordInput(this.passwordSelector, {
            onToggleInput: ($newPasswordInput) => {
                this.$passwordField.off('input');
                this.$passwordField.replaceWith($newPasswordInput);
                this.$passwordField.on('input', this.onInput.bind(this));
            }
        });
        this.$passwordField.on('input', this.onInput.bind(this));
    }
    cleanup() {
        delete this.passwordInput;
        delete this.passwordInput;
        this.$passwordField.off('input', this.onInput.bind(this));
    }
    validate() {
        const passwordLength = this.$passwordField.val().length;
        // @ts-ignore
        if (passwordLength < window.minPasswordLength) {
            return Craft.t('yii', '{attribute} should contain at least {min, number} {min, plural, one{character} other{characters}}.', {
                attribute: Craft.t('app', 'Password'),
                // @ts-ignore
                min: window.minPasswordLength,
            });
        }
        // @ts-ignore
        if (passwordLength > window.maxPasswordLength) {
            return Craft.t('yii', '{attribute} should contain at most {max, number} {max, plural, one{character} other{characters}}.', {
                attribute: Craft.t('app', 'Password'),
                // @ts-ignore
                max: window.maxPasswordLength,
            });
        }
        return true;
    }
    returnFormData() {
        return {
            password: this.$passwordField.val(),
        };
    }
}
new PasswordStep();
