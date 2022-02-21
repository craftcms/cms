import SubmitEvent = JQuery.SubmitEvent;
import {AuthenticationRequestData} from "./AuthenticationChainHandler";

export abstract class AuthenticationStep
{
    protected validateOnInput = false;

    protected stepType: string;

    /**
     * Validate the inputs. Return `true` for valid or a string as the error message.
     */
    protected abstract validate(): true | string;

    /**
     * Return the form data gathered from the appropriate inputs.
     */
    protected abstract returnFormData(): AuthenticationRequestData;

    /**
     * Initialize the authentication step.
     */
    public abstract init(): void;

    /**
     * Clean up the step as it stops being the current step.
     */
    public abstract cleanup(): void;

    protected constructor(stepType: string)
    {
        this.stepType = stepType;
        Craft.AuthenticationChainHandler.registerAuthenticationStep(stepType, this);
        this.doInit();
    }

    protected doInit() {
        this.cleanup();
        this.init();
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
