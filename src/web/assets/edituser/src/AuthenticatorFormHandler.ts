import {AuthenticationSetupFormHandler} from "./AuthenticationSetupFormHandler";

enum OperationType
{
    update,
    remove
}

export class AuthenticatorFormHandler extends AuthenticationSetupFormHandler
{
    readonly endpoint = 'authentication/update-authenticator-settings';

    private disabled = false;

    private get $container () {
        return $('#authenticator-settings');
    }

    public get $updateAuthenticator()
    {
        return $('#update-authenticator');
    }

    public get $removeAuthenticator()
    {
        return $('#remove-authenticator');
    }

    public get $authenticatorField()
    {
        return $('#authenticator-code');
    }

    protected get $status()
    {
        return $('#authenticator-status');
    }

    protected codeModal: any;

    /**
     * @inheritdoc
     */
    protected attachEvents()
    {
        this.$authenticatorField.on('keydown', (event) => {
            if (event.key == "Enter") {
                event.stopImmediatePropagation();
                this.handleAuthenticatorUpdate(OperationType.update);
            }
        })

        this.$updateAuthenticator.on('click', (event) => {
            event.stopImmediatePropagation();
            this.handleAuthenticatorUpdate(OperationType.update);
        });

        this.$removeAuthenticator.on('click', (event) => {
            event.stopImmediatePropagation();
            this.handleAuthenticatorUpdate(OperationType.remove);
        });
    }

    /**
     * Handle authenticator setting update.
     *
     * @protected
     */
    protected handleAuthenticatorUpdate(type: OperationType)
    {
        if (this.disabled || (type == OperationType.update && (this.$authenticatorField.val() as string).length === 0)) {
            return;
        }

        this.disable();

        let data;

        switch (type) {
            case OperationType.update:
                data = {
                    'authenticator-code': this.$authenticatorField.val()
                };
                break;
            case OperationType.remove:
                data = {
                    'detach': 'detach'
                };
        }

        const submitUpdate = (payload) => {
            this.setStatus(Craft.t('app', 'Updating the authenticator settings'));

            Craft.sendActionRequest('POST', this.endpoint, {data: payload})
                .then((response) => {
                    this.enable();

                    if (response?.data?.html) {
                        this.$container.replaceWith(response.data.html);
                        this.attachEvents();
                    }

                    if (response?.data?.message) {
                        this.setStatus(response.data.message, false, 750);
                    }

                    if (response?.data?.codeHtml) {
                        const $codeHtml = $('<div class="modal secure fitted"></div>').append($(response.data.codeHtml));

                        $codeHtml.find('#close-codes').on('click', () => {
                            this.codeModal.hide();
                        })
                        this.codeModal = new Garnish.Modal($codeHtml, {
                            closeOtherModals: false,
                            hideOnEsc: false,
                            hideOnShadeClick: false,
                            onFadeOut: () => {
                                $codeHtml.find('.codes').remove();
                                this.codeModal.destroy();
                                this.codeModal = null;
                            },
                        });
                    }
                })
                .catch(({response}) => {
                    this.enable();

                    if (response?.data?.message) {
                        this.setErrorStatus(response.data.message);
                    }
                });
        }

        this.setStatus(Craft.t('app', 'Waiting for elevated session'));

        Craft.elevatedSessionManager.requireElevatedSession(
            () => submitUpdate(data),
            () => {
                this.enable();
                this.clearStatus();
            }
        );
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
