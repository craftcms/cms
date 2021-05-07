"use strict";
class AuthenticatorCode extends VerificationCode {
    constructor() {
        super();
        this.stepType = 'craft\\authentication\\type\\mfa\\AuthenticatorCode';
    }
}
new AuthenticatorCode();
