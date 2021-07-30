import SubmitEvent = JQuery.SubmitEvent;

abstract class AuthenticationStep
{
    protected validateOnInput = false;

    protected stepType: string;

    protected $loginForm: JQuery;
    protected $submit: JQuery;

    protected abstract validate(): true | string;
    protected abstract returnFormData(): AuthenticationRequest;

    protected constructor(stepType: string)
    {
        this.stepType = stepType;
        Craft.AuthenticationChainHandler.registerStepHandler(stepType, this.prepareData.bind(this));
        this.$loginForm = Craft.AuthenticationChainHandler.loginHandler.$loginForm;
        this.$submit = Craft.AuthenticationChainHandler.loginHandler.$submit;
    }

    /**
     * @param ev
     */
    public onInput(ev: any)
    {
        if (this.validateOnInput && this.validate() === true) {
            Craft.AuthenticationChainHandler.clearErrors();
        }
    }

    /**
     *
     * @param ev
     */
    public async prepareData()
    {
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
