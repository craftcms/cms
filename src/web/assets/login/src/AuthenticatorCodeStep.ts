import {VerificationCode} from "./VerificationCode";

export class AuthenticatorCodeStep extends VerificationCode
{
    constructor()
    {
        super('craft\\authentication\\type\\mfa\\AuthenticatorCode');
    }
}
