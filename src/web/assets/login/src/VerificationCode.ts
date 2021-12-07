import {AuthenticationStep} from "./AuthenticationStep";

export abstract class VerificationCode extends AuthenticationStep
{
    protected constructor(stepType: string)
    {
        super(stepType);
    }

    get $verificationCode() { return $('#verificationCode'); }

    public init()
    {
        this.$verificationCode.on('input', this.onInput.bind(this));
    }

    public cleanup() {
        this.$verificationCode.off('input', this.onInput.bind(this));
    }

    public validate()
    {
        const verificationCode = this.$verificationCode.val() as string;

        if (verificationCode.length === 0) {
            return Craft.t('app', 'Please enter a verification code');
        }

        return true;
    }

    protected returnFormData()
    {
        return {
            "verification-code": this.$verificationCode.val()
        };
    }
}
