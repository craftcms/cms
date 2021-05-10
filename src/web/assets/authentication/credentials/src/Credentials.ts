class Credentials extends AuthenticationStep
{
    private loginNameSelector = '#loginName';
    private passwordSelector = '#password';

    constructor()
    {
        super('craft\\authentication\\type\\Credentials');

        new Craft.PasswordInput(, {
            onToggleInput: ($newPasswordInput: JQuery): void => {
                this.getPasswordInput().off('input');
                this.getPasswordInput().replaceWith($newPasswordInput);
                this.getPasswordInput().on('input', this.onInput.bind(this));
            }
        });

        this.$loginForm.on('input', this.loginNameSelector, this.onInput.bind(this));
        this.$loginForm.on('input', this.passwordSelector, this.onInput.bind(this));
    }

    public validate()
    {
        const loginNameVal = this.getLoginNameInput().val() as string;
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

        const passwordLength = (this.getPasswordInput().val() as string).length;

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

    protected returnFormData(): AuthenticationRequest
    {
        return {
            loginName: this.getLoginNameInput().val(),
            password: this.getPasswordInput().val(),
        };
    }

    protected getLoginNameInput(): JQuery
    {
        return this.$loginForm.find(this.loginNameSelector);
    }

    protected getPasswordInput(): JQuery
    {
        return this.$loginForm.find(this.passwordSelector);
    }

}

new Credentials();
