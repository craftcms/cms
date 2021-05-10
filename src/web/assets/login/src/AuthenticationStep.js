"use strict";
class AuthenticationStep {
    constructor(stepType) {
        this.validateOnInput = false;
        this.stepType = stepType;
        Craft.LoginForm.registerStepHandler(stepType, this.prepareData.bind(this));
        this.$loginForm = Craft.LoginForm.$loginForm;
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
