class EmailCode extends VerificationCode
{
    constructor()
    {
        super('craft\\authentication\\type\\mfa\\EmailCode');
    }
}

new EmailCode();
