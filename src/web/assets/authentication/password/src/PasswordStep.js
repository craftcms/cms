"use strict";
class PasswordStep extends AuthenticationStep {
    constructor() {
        super('craft\\authentication\\type\\Password');
        this.passwordSelector = '#password';
        new Craft.PasswordInput(this.passwordSelector, {
            onToggleInput: ($newPasswordInput) => {
                this.getPasswordInput().off('input');
                this.getPasswordInput().replaceWith($newPasswordInput);
                this.getPasswordInput().on('input', this.onInput.bind(this));
            }
        });
        this.$loginForm.on('input', this.passwordSelector, this.onInput.bind(this));
    }
    validate() {
        const passwordLength = this.getPasswordInput().val().length;
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
            password: this.getPasswordInput().val(),
        };
    }
    getPasswordInput() {
        return this.$loginForm.find(this.passwordSelector);
    }
}
new PasswordStep();
