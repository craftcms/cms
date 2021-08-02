"use strict";
class AuthenticationStep {
    constructor(stepType) {
        this.validateOnInput = false;
        this.stepType = stepType;
        Craft.AuthenticationChainHandler.registerAuthenticationStep(stepType, this);
        this.doInit();
    }
    get $loginForm() { return Craft.AuthenticationChainHandler.loginForm.$loginForm; }
    get $submit() { return Craft.AuthenticationChainHandler.loginForm.$submit; }
    doInit() {
        this.cleanup();
        this.init();
    }
    /**
     * @param ev
     */
    onInput(ev) {
        if (this.validateOnInput && this.validate() === true) {
            Craft.AuthenticationChainHandler.clearErrors();
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
