"use strict";
class LoginStep extends AuthenticationStep {
    constructor() {
        super('craft\\authentication\\type\\Login');
        this.loginNameSelector = '#loginName';
        this.passwordSelector = '#password';
        new Craft.PasswordInput(this.passwordSelector, {
            onToggleInput: ($newPasswordInput) => {
                this.getPasswordInput().off('input');
                this.getPasswordInput().replaceWith($newPasswordInput);
                this.getPasswordInput().on('input', this.onInput.bind(this));
            }
        });
        this.$loginForm.on('input', this.loginNameSelector, this.onInput.bind(this));
        this.$loginForm.on('input', this.passwordSelector, this.onInput.bind(this));
    }
    validate() {
        const loginNameVal = this.getLoginNameInput().val();
        if (loginNameVal.length === 0) {
            // @ts-ignore
            if (window.useEmailAsUsername) {
                return Craft.t('app', 'Invalid email.');
            }
            return Craft.t('app', 'Invalid username or email.');
        }
        // @ts-ignore
        if (window.useEmailAsUsername && !loginNameVal.match('.+@.+\..+')) {
            return Craft.t('app', 'Invalid email.');
        }
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
            loginName: this.getLoginNameInput().val(),
            password: this.getPasswordInput().val(),
        };
    }
    getLoginNameInput() {
        return this.$loginForm.find(this.loginNameSelector);
    }
    getPasswordInput() {
        return this.$loginForm.find(this.passwordSelector);
    }
}
new LoginStep();
