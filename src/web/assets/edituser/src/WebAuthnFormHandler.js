"use strict";
class WebAuthnFormHandler {
    constructor() {
        $('#attach-webauthn').on('click', (ev) => {
            ev.stopImmediatePropagation();
            const $button = $(ev.target);
            const keyCredentialOptions = {
                challenge: Uint8Array.from($button.data('challenge'), c => c.charCodeAt(0)),
                rp: {
                    id: window.location.hostname,
                    name: $button.data('rp-name'),
                },
                user: {
                    displayName: $button.data('display-name'),
                    name: $button.data('name'),
                    id: Uint8Array.from($button.data('uid'), c => c.charCodeAt(0))
                },
                pubKeyCredParams: [{ alg: -7, type: "public-key" }],
                timeout: 60000
            };
            this.createCredentials(keyCredentialOptions).then((credentials) => {
                const requestData = {
                    credentialId: credentials.id,
                    attestationObject: btoa(String.fromCharCode(...new Uint8Array(credentials.response.attestationObject))),
                    clientDataJSON: btoa(String.fromCharCode(...new Uint8Array(credentials.response.clientDataJSON))),
                };
                console.log(requestData);
            });
            return false;
        });
    }
    /**
     * Get the WebAuthn server options based on a random string and user info.
     *
     * @param randomString
     * @param userInfo
     */
    async createCredentials(keyCredentialOptions) {
        const credentials = await navigator.credentials.create({
            publicKey: keyCredentialOptions
        }).catch((err) => { alert(err); });
        if (!credentials) {
            throw "Failed to create credentials";
        }
        return credentials;
    }
}
new WebAuthnFormHandler();
