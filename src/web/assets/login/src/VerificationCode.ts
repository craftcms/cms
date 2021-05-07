abstract class VerificationCode extends AuthenticationStep
{
    protected $verificationCode = $('#verificationCode');

    protected constructor()
    {
        super();

        this.$verificationCode.parents('.authentication-chain').data('handler', this.prepareData.bind(this));
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
