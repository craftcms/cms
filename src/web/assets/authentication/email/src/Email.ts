class Email
{
    private $email = $('#email');
    private validateOnInput = false;

    constructor()
    {
        Craft.LoginForm.registerStepHandler(this.prepareData.bind(this), true);
    }

    public validate()
    {
        const verificationCode = this.$email.val() as string;
        if (verificationCode.length === 0) {
            return Craft.t('app', 'Please enter a valid email address');
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
            "email": this.$email.val(),
        };
    }
}

new Email();
