"use strict";
class WebAuthnStep extends AuthenticationStep {
    constructor() {
        super('craft\\authentication\\type\\mfa\\WebAuthn');
    }
    get $button() { return $('#verify-webauthn'); }
    ;
    validate() {
        this.$button.addClass('hidden');
        return true;
    }
    init() {
        this.$loginForm.trigger('submit');
        this.$button.on('click', this.onButtonClick.bind(this));
        this.$submit.addClass('hidden');
    }
    cleanup() {
        this.$button.off('click', this.onButtonClick.bind(this));
        this.$submit.removeClass('hidden');
    }
    /**
     * Submit the form again, when the authentication button is clicked.
     */
    onButtonClick() {
        this.$loginForm.trigger('submit');
    }
    ;
    async returnFormData() {
        const optionData = this.$button.data('request-options');
        // Sort-of deep copy
        const requestOptions = Object.assign({}, optionData);
        if (optionData.allowCredentials) {
            requestOptions.allowCredentials = [...optionData.allowCredentials];
        }
        // proprietary base 64 decode, for some reason
        requestOptions.challenge = atob(requestOptions.challenge.replace(/-/g, '+').replace(/_/g, '/'));
        // Unpack to binary data
        requestOptions.challenge = Uint8Array.from(requestOptions.challenge, c => c.charCodeAt(0));
        for (const idx in requestOptions.allowCredentials) {
            let allowed = requestOptions.allowCredentials[idx];
            requestOptions.allowCredentials[idx] = {
                id: Uint8Array.from(atob(allowed.id.replace(/-/g, '+').replace(/_/g, '/')), c => c.charCodeAt(0)),
                type: allowed.type
            };
        }
        let credential;
        // Finally, try to get the credentials based on the provided data.
        try {
            credential = await navigator.credentials.get({
                publicKey: requestOptions
            });
        }
        catch (error) {
            this.$button.removeClass('hidden');
            throw Craft.t('app', 'Failed to authenticate');
        }
        const response = credential.response;
        // Prep and return the data for the request
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
