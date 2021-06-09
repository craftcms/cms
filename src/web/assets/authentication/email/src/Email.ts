class Email extends AuthenticationStep
{
    private inputSelector = '#email';

    constructor()
    {
        super('craft\\authentication\\type\\Email');
        this.$loginForm.on('input', this.inputSelector, this.onInput.bind(this));
    }

    protected validate()
    {
        const emailAddress = this.getEmailInput().val() as string;
        if (emailAddress.length === 0) {
            return Craft.t('app', 'Please enter a valid email address');
        }

        return true;
    }

    protected returnFormData()
    {
        return {
            "email": this.getEmailInput().val(),
        };
    }

    protected getEmailInput(): JQuery
    {
        return this.$loginForm.find(this.inputSelector);
    }
}

new Email();
