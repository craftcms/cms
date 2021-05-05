"use strict";
class Credentials extends AuthenticationStep {
    constructor() {
        super();
        this.$loginNameInput = $('#loginName');
        this.$passwordInput = $('#password');
        this.stepType = "craft\\authentication\\type\\Credentials";
        const isRecoveryStep = this.$loginNameInput.parents('#recovery-container').length > 0;
        Craft.LoginForm.registerStepHandler(this.prepareData.bind(this), isRecoveryStep);
        new Craft.PasswordInput(this.$passwordInput, {
            onToggleInput: ($newPasswordInput) => {
                this.$passwordInput.off('input');
                this.$passwordInput = $newPasswordInput;
                this.$passwordInput.on('input', this.onInput.bind(this));
            }
        });
        this.$loginNameInput.on('input', this.onInput.bind(this));
        this.$passwordInput.on('input', this.onInput.bind(this));
    }
    validate() {
        const loginNameVal = this.$loginNameInput.val();
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
        const passwordLength = this.$passwordInput.val().length;
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
            loginName: this.$loginNameInput.val(),
            password: this.$passwordInput.val(),
        };
    }
}
new Credentials();
