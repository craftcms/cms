class EmailCodeStep extends VerificationCode
{
    constructor()
    {
        super('craft\\authentication\\type\\mfa\\EmailCode');
    }
}

new EmailCodeStep();
