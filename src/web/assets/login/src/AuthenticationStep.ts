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
     *
     * @param ev
     */
    public onInput(ev: any) : void
    {
        if (this.validateOnInput && this.validate() === true) {
            Craft.LoginForm.clearErrors();
        }
    }

    public prepareData(ev: SubmitEvent): AuthenticationRequest | string
    {
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
