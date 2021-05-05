"use strict";
class AuthenticationStep {
    constructor() {
        this.validateOnInput = false;
        this.stepType = '';
    }
    /**
     *
     * @param ev
     */
    onInput(ev) {
        if (this.validateOnInput && this.validate() === true) {
            Craft.LoginForm.clearErrors();
        }
    }
    prepareData(ev) {
        const error = this.validate();
        if (error !== true) {
            this.validateOnInput = true;
            return error;
        }
        this.validateOnInput = false;
        const returnData = this.returnFormData();
        returnData.stepType = this.stepType;
        return returnData;
    }
}
