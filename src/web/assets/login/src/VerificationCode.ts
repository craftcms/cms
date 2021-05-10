abstract class VerificationCode extends AuthenticationStep
{
    private inputSelector = '#verificationCode';

    protected constructor(stepType: string)
    {
        super(stepType);
        this.$loginForm.on('input', this.inputSelector, this.onInput.bind(this));
    }

    public validate()
    {
        const verificationCode = this.getVerificationCodeInput().val() as string;

        if (verificationCode.length === 0) {
            return Craft.t('app', 'Please enter a verification code');
        }

        return true;
    }

    protected returnFormData(): AuthenticationRequest
    {
        return {
            "verification-code": this.getVerificationCodeInput().val(),
        };
    }

    protected getVerificationCodeInput(): JQuery
    {
        return this.$loginForm.find(this.inputSelector);
    }
}
