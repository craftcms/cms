export abstract class AuthenticationSetupFormHandler
{
    protected abstract get $status();

    /**
     * Attach the listeners for field events.
     *
     * @private
     */
    protected abstract attachEvents();

    constructor()
    {
        this.attachEvents();
    }

    /**
     * Clears the status and removes the spinner.
     *
     * @protected
     */
    protected clearStatus() {
        this.setStatus('', false);
    }

    /**
     * Display a status message and an optional spinner.
     *
     * @param message
     * @param showSpinner
     * @protected
     */
    protected setStatus(message: string, showSpinner: boolean = true)
    {
        if (showSpinner) {
            message = `<div class="spinner"></div><span>${message}</span>`;
        }

        this.$status.html(message);
    }

    /**
     * Set an error status for the user to see.
     *
     * @param message
     * @protected
     */
    protected setErrorStatus(message: string)
    {
        this.$status.html(`<div class="error">${message}</div<`);
    }
}
