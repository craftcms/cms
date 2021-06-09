import SubmitEvent = JQuery.SubmitEvent;

abstract class AuthenticationStep
{
    protected validateOnInput = false;

    protected stepType: string;

    protected $loginForm: JQuery;

    protected abstract validate(): true | string;
    protected abstract returnFormData(): AuthenticationRequest;

    protected constructor(stepType: string)
    {
        this.stepType = stepType;
        Craft.LoginForm.registerStepHandler(stepType, this.prepareData.bind(this));
        this.$loginForm = Craft.LoginForm.$loginForm;
    }

    /**
     * @param ev
     */
    public onInput(ev: any)
    {
        if (this.validateOnInput && this.validate() === true) {
            Craft.LoginForm.clearErrors();
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
