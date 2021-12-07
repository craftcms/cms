import {AuthenticationStep} from "./AuthenticationStep";

export class PasswordStep extends AuthenticationStep
{
    private passwordSelector = '#password';
    private passwordInput?: any;

    constructor()
    {
        super('craft\\authentication\\type\\Password');
    }

    get $passwordField() { return $(this.passwordSelector);}

    public init()
    {
        this.passwordInput = new Craft.PasswordInput(this.passwordSelector, {
            onToggleInput: ($newPasswordInput: JQuery): void => {
                this.$passwordField.off('input');
                this.$passwordField.replaceWith($newPasswordInput);
                this.$passwordField.on('input', this.onInput.bind(this));
            }
        });

        this.$passwordField.on('input', this.onInput.bind(this));
    }

    public cleanup()
    {
        delete this.passwordInput;
        delete this.passwordInput;
        this.$passwordField.off('input', this.onInput.bind(this));
    }

    public validate()
    {
        const passwordLength = (this.$passwordField.val() as string).length;

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

    protected returnFormData()
    {
        return {
            password: this.$passwordField.val(),
        };
    }
}
