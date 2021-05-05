class EmailCode extends AuthenticationStep
{
    private $verificationCode = $('#verificationCode');
    protected stepType = "craft\\authentication\\type\\mfa\\EmailCode";

    constructor()
    {
        super();
        const isRecoveryStep = this.$verificationCode.parents('#recovery-container').length > 0;
        Craft.LoginForm.registerStepHandler(this.prepareData.bind(this), isRecoveryStep);

        this.$verificationCode.on('input', this.onInput.bind(this));
    }

    public validate()
    {
        const verificationCode = this.$verificationCode.val() as string;
        if (verificationCode.length === 0) {
            return Craft.t('app', 'Please enter a verification code');
        }

        return true;
    }

    protected returnFormData(): AuthenticationRequest
    {
        return {
            "verification-code": this.$verificationCode.val(),
        };
    }
}

new EmailCode();
