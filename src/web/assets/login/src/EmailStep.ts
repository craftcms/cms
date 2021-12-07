import {AuthenticationStep} from "./AuthenticationStep";

export class EmailStep extends AuthenticationStep
{
    constructor()
    {
        super('craft\\authentication\\type\\Email');
    }

    get $inputField () { return $('#email');}

    public init()
    {
        this.$inputField.on('input', this.onInput.bind(this));
    }

    public cleanup()
    {
        this.$inputField.off('input', this.onInput.bind(this));
    }

    protected validate()
    {
        const emailAddress = this.$inputField.val() as string;

        if (emailAddress.length === 0) {
            return Craft.t('app', 'Please enter a valid email address');
        }

        return true;
    }

    protected returnFormData()
    {
        return {
            "email": this.$inputField.val(),
        };
    }
}
