class AuthenticatorFormHandler
{

    readonly endpoint = 'authentication/update-authenticator-settings';

    private disabled = false;
    private $container = $('#authenticator-settings');

    constructor()
    {
        this.attachEvents();

    }

    /**
     * Attach the listeners for field events.
     *
     * @private
     */
    private attachEvents()
    {
        $('.authenticator-field').on('keydown', (event) => {
            if (event.key == "Enter") {
                event.stopImmediatePropagation();
                this.handleAuthenticatorUpdate();
                return false;
            }
        })
        $('#update-authenticator').on('click', (event) => {
            event.stopImmediatePropagation();
            this.handleAuthenticatorUpdate();
        });
    }

    /**
     * Handle authenticator setting update.
     *
     * @protected
     */
    protected handleAuthenticatorUpdate()
    {
        if (Craft.elevatedSessionManager.fetchingTimeout) {
            return;
        }

        const $detach = $('.authenticator-field.detach');
        const $verificationCode1 = $('#verification-code-1');
        const $verificationCode2 = $('#verification-code-2');

        // If detaching
        if ($detach.length > 0) {
            if (($detach.val() as string).length > 0) {
                if ($detach.val() !== 'detach') {
                    Garnish.shake($detach);
                } else {
                    Craft.elevatedSessionManager.requireElevatedSession(this.submitAuthenticatorUpdate.bind(this));
                }
            }
        } else {
            if (($verificationCode1.val() as string).length == 0 || ($verificationCode2.val() as string).length == 0) {
                return;
            }
            Craft.elevatedSessionManager.requireElevatedSession(this.submitAuthenticatorUpdate.bind(this));
        }
    }

    /**
     * Submit authenticator setting update.
     * @protected
     */
    protected submitAuthenticatorUpdate()
    {
        if (this.disabled) {
            return;
        }

        this.disable();

        const $fields = $('input.authenticator-field');

        let data: {
            [key: string]: string
        } = {};

        for (const field of $fields) {
            data[field.getAttribute('name')!] = (field as HTMLInputElement).value;
        }

        Craft.postActionRequest(this.endpoint, data, (response: any, textStatus: string) => {
            this.enable();

            if (response.message) {
                alert(response.message);
            }

            if (response.html) {
                this.$container.replaceWith(response.html);
                this.$container = $('#authenticator-settings');
                this.attachEvents();
            }
        });
    }

    /**
     * Disable the setting fields.
     *
     * @protected
     */
    protected disable()
    {
        this.disabled = true;
        this.$container.fadeTo(100, 0.5);
    }

    /**
     * Enable the setting fields.
     *
     * @protected
     */
    protected enable()
    {
        this.disabled = false;
        this.$container.fadeTo(100, 1);
    }
}

new AuthenticatorFormHandler();
