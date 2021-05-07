class EmailCode extends VerificationCode
{
    protected stepType = 'craft\\authentication\\type\\mfa\\EmailCode';

    constructor()
    {
        super();
    }
}

new EmailCode();
