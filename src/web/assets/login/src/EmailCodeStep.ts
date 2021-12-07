import {VerificationCode} from "./VerificationCode";

export class EmailCodeStep extends VerificationCode
{
    constructor()
    {
        super('craft\\authentication\\type\\mfa\\EmailCode');
    }
}
