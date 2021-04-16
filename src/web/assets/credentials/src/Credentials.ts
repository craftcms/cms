class Credentials
{
    private $loginNameInput = $('#loginName');
    private $passwordInput = $('#password');
    private validateOnInput = false;

    constructor()
    {
        Craft.LoginForm.registerStepHandler(this.prepareData.bind(this));

        new Craft.PasswordInput(this.$passwordInput, {
            onToggleInput: ($newPasswordInput: JQuery): void => {
                this.$passwordInput.off('input');
                this.$passwordInput = $newPasswordInput;
                this.$passwordInput.on('input', this.onInput.bind(this));
            }
        });

        this.$loginNameInput.on('input', this.onInput.bind(this));
        this.$passwordInput.on('input', this.onInput.bind(this));

        // TODO this class must handle forgotten password functionality.
    }

    public validate()
    {
        const loginNameVal = this.$loginNameInput.val() as string;
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

        const passwordLength = (this.$passwordInput.val() as string).length;

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

    public onInput(ev: any) : void
    {
        if (this.validateOnInput && this.validate() === true) {
            Craft.LoginForm.clearErrors();
        }
    }

    public prepareData(ev: any): AuthenticationRequest | string
    {
        const error = this.validate();
        if (error !== true) {
            this.validateOnInput = true;
            return error;
        }

        this.validateOnInput = false;

        return {
            loginName: this.$loginNameInput.val(),
            password: this.$passwordInput.val(),
        };
    }
}

new Credentials();
