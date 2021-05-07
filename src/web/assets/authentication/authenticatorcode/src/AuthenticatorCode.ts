class AuthenticatorCode extends VerificationCode
{
    protected stepType = 'craft\\authentication\\type\\mfa\\AuthenticatorCode';

    constructor()
    {
        super();
    }
}

new AuthenticatorCode();
