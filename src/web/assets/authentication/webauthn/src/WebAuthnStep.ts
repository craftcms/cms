class WebAuthnStep extends AuthenticationStep
{
    readonly $button = $('#verify-webauthn');

    constructor()
    {
        super('craft\\authentication\\type\\mfa\\WebAuthn');
        this.$loginForm.trigger('submit');
        this.$button.on('click', () => { this.$loginForm.trigger('submit')});
        this.$submit.hide();
    }

    public validate(): true
    {
        this.$button.addClass('hidden');
        return true;
    }

    protected async returnFormData()
    {
        const optionData = this.$button.data('request-options');

        // Sort-of deep copy
        const requestOptions = {...optionData};

        if (optionData.allowCredentials) {
            requestOptions.allowCredentials = [...optionData.allowCredentials];
        }

        // proprietary base 64 decode, for some reason
        requestOptions.challenge = atob(requestOptions.challenge.replace(/-/g, '+').replace(/_/g, '/'));

        // Unpack to binary data
        requestOptions.challenge = Uint8Array.from(requestOptions.challenge as string, c => c.charCodeAt(0));

        for (const idx in requestOptions.allowCredentials) {
            let allowed = requestOptions.allowCredentials[idx];

            requestOptions.allowCredentials[idx] = {
                id: Uint8Array.from(atob(allowed.id.replace(/-/g, '+').replace(/_/g, '/')) as string, c => c.charCodeAt(0)),
                type: allowed.type
            };
        }

        let credential: PublicKeyCredential | null;

        try {
            credential = await navigator.credentials.get({
                publicKey: requestOptions
            }) as PublicKeyCredential;
        } catch (error) {

            this.$button.removeClass('hidden');
            throw Craft.t('app', 'Failed to authenticate');
        }

        const response = credential.response as AuthenticatorAssertionResponse;

        return {
            credentialResponse: {
                id: credential.id,
                rawId: credential.id,
                response: {
                    authenticatorData: btoa(String.fromCharCode(...new Uint8Array(response.authenticatorData))),
                    clientDataJSON: btoa(String.fromCharCode(...new Uint8Array(response.clientDataJSON))),
                    signature: btoa(String.fromCharCode(...new Uint8Array(response.signature))),
                    userHandle: response.userHandle ? btoa(String.fromCharCode(...new Uint8Array(response.userHandle))) : null,
                },
                type: credential.type,
            }
        };
    }
}

new WebAuthnStep();
