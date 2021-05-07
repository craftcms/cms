"use strict";
class EmailCode extends VerificationCode {
    constructor() {
        super();
        this.stepType = 'craft\\authentication\\type\\mfa\\EmailCode';
    }
}
new EmailCode();
