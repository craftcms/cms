interface CraftUserInfo {
    username: string,
    displayName: string,
    uid: string
}

class WebAuthnFormHandler
{
    constructor()
    {
        $('#attach-webauthn').on('click', (ev) => {
            ev.stopImmediatePropagation();
            const $button = $(ev.target);

            const keyCredentialOptions = {
                challenge: Uint8Array.from($button.data('challenge') as string, c => c.charCodeAt(0)),
                rp: {
                    id: window.location.hostname,
                    name: $button.data('rp-name'),
                },
                user: {
                    displayName: $button.data('display-name'),
                    name: $button.data('name'),
                    id: Uint8Array.from($button.data('uid') as string, c => c.charCodeAt(0))
                },
                pubKeyCredParams: [{alg: -7, type: "public-key"}],
                timeout: 60000
            } as PublicKeyCredentialCreationOptions;

            this.createCredentials(keyCredentialOptions).then(( credentials) => {
                const requestData = {
                    credentialId: credentials.id,
                    attestationObject: btoa(String.fromCharCode(...new Uint8Array((credentials.response as AuthenticatorAttestationResponse).attestationObject))),
                    clientDataJSON: btoa(String.fromCharCode(...new Uint8Array(credentials.response.clientDataJSON))),
                }

                console.log(requestData);
            })

            return false;
        });
    }


    /**
     * Get the WebAuthn server options based on a random string and user info.
     *
     * @param randomString
     * @param userInfo
     */
    private async createCredentials(keyCredentialOptions: PublicKeyCredentialCreationOptions): Promise<PublicKeyCredential>
    {
        const credentials = <PublicKeyCredential> await navigator.credentials.create({
            publicKey: keyCredentialOptions
        }).catch((err) => {alert(err)});

        if (!credentials) {
            throw "Failed to create credentials";
        }

        return credentials;
    }
}

new WebAuthnFormHandler();
