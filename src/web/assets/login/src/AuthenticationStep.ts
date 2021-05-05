abstract class AuthenticationStep
{
    protected validateOnInput = false;

    protected stepType = '';

    protected abstract validate(): true | string;
    protected abstract returnFormData(): AuthenticationRequest;

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

    public prepareData(ev: any): AuthenticationRequest | string
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
