class EmailCode
{
    private $verificationCode = $('#verificationCode');
    private validateOnInput = false;

    constructor()
    {
        Craft.LoginForm.registerStepHandler(this.prepareData.bind(this));
    }

    public validate()
    {
        const verificationCode = this.$verificationCode.val() as string;
        if (verificationCode.length === 0) {
            return Craft.t('app', 'Please enter a verification code');
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
            "verification-code": this.$verificationCode.val(),
        };
    }
}

new EmailCode();
