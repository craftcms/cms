"use strict";
class AuthenticationStep {
    constructor(stepType) {
        this.validateOnInput = false;
        this.stepType = stepType;
        Craft.LoginForm.registerStepHandler(stepType, this.prepareData.bind(this));
        this.$loginForm = Craft.LoginForm.$loginForm;
    }
    /**
     * @param ev
     */
    onInput(ev) {
        if (this.validateOnInput && this.validate() === true) {
            Craft.LoginForm.clearErrors();
        }
    }
    /**
     *
     * @param ev
     */
    async prepareData() {
        const error = this.validate();
        if (error !== true) {
            this.validateOnInput = true;
            throw error;
        }
        this.validateOnInput = false;
        let data = await this.returnFormData();
        data.stepType = this.stepType;
        return data;
    }
}
