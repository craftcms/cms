"use strict";
class WebAuthnFormHandler {
    constructor() {
        this.attachEndpoint = 'authentication/attach-web-authn-credentials';
        this.disabled = false;
        this.$container = $('#webauthn-settings');
        this.attachEvents();
    }
    attachEvents() {
        WebAuthnFormHandler.$button = $('#attach-webauthn');
        WebAuthnFormHandler.$button.on('click', (ev) => {
            ev.stopImmediatePropagation();
            const $button = $(ev.target);
            const optionData = $button.data('credential-options');
            // Sort-of deep copy
            const keyCredentialOptions = Object.assign(Object.assign({}, optionData), { user: Object.assign({}, optionData.user) });
            if (optionData.excludeCredentials) {
                keyCredentialOptions.excludeCredentials = [...optionData.excludeCredentials];
            }
            // proprietary base 64 decode, for some reason
            keyCredentialOptions.challenge = atob(keyCredentialOptions.challenge.replace(/-/g, '+').replace(/_/g, '/'));
            keyCredentialOptions.user.id = atob(keyCredentialOptions.user.id.replace(/-/g, '+').replace(/_/g, '/'));
            // Unpack to binary data
            keyCredentialOptions.challenge = Uint8Array.from(keyCredentialOptions.challenge, c => c.charCodeAt(0));
            keyCredentialOptions.user.id = Uint8Array.from(keyCredentialOptions.user.id, c => c.charCodeAt(0));
            for (const idx in keyCredentialOptions.excludeCredentials) {
                let excluded = keyCredentialOptions.excludeCredentials[idx];
                keyCredentialOptions.excludeCredentials[idx] = {
                    id: Uint8Array.from(atob(excluded.id.replace(/-/g, '+').replace(/_/g, '/')), c => c.charCodeAt(0)),
                    type: excluded.type
                };
            }
            this.createCredentials(keyCredentialOptions).then((credentials) => {
                Craft.elevatedSessionManager.requireElevatedSession(() => this.attachWebAuthnCredential(credentials));
            }).catch((err) => {
                console.log(err);
            });
            return false;
        });
    }
    attachWebAuthnCredential(credentials) {
        if (this.disabled) {
            return false;
        }
        this.disable();
        const credentialName = prompt(Craft.t('app', 'Please enter a name for the credentials'), 'Secure credentials');
        const requestData = {
            credentialName: credentialName,
            credentials: {
                id: credentials.id,
                rawId: credentials.id,
                type: credentials.type,
                response: {
                    attestationObject: btoa(String.fromCharCode(...new Uint8Array(credentials.response.attestationObject))),
                    clientDataJSON: btoa(String.fromCharCode(...new Uint8Array(credentials.response.clientDataJSON))),
                }
            }
        };
        Craft.postActionRequest(this.attachEndpoint, requestData, (response, textStatus) => {
            if (response.html) {
                this.$container.replaceWith(response.html);
                this.$container = $('#webauthn-settings');
                this.attachEvents();
            }
            if (response.footHtml) {
                const jsFiles = response.footHtml.match(/([^"']+\.js)/gm);
                const existingSources = Array.from(document.scripts).map(node => node.getAttribute('src')).filter(val => val && val.length > 0);
                // For some reason, Chrome will fail to load sourcemap properly when jQuery append is used
                // So roll our own JS file append-thing.
                if (jsFiles) {
                    for (const jsFile of jsFiles) {
                        if (!existingSources.includes(jsFile)) {
                            let node = document.createElement('script');
                            node.setAttribute('src', jsFile);
                            document.body.appendChild(node);
                        }
                    }
                    // If that fails, use Craft's thing.
                }
                else {
                    Craft.appendFootHtml(response.footHtml);
                }
            }
            this.enable();
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
        }).catch((err) => {
            console.log(err);
        });
        if (!credentials) {
            throw "Failed to create credentials";
        }
        return credentials;
    }
    /**
     * Disable the setting fields.
     *
     * @protected
     */
    disable() {
        this.disabled = true;
        WebAuthnFormHandler.$button.fadeTo(100, 0.5);
    }
    /**
     * Enable the setting fields.
     *
     * @protected
     */
    enable() {
        this.disabled = false;
        WebAuthnFormHandler.$button.fadeTo(100, 1);
    }
    /**
     * Remove an excluded credential by its id.
     *
     * @param credentialId
     */
    static removeExcludedCredential(credentialId) {
        const optionData = WebAuthnFormHandler.$button.data('credential-options');
        let newExcluded = [];
        for (const excluded of optionData.excludeCredentials) {
            // Adjust for the proprietary base64 encode thing.
            if (excluded.id.replace(/[-_=+\/]/g, '') !== credentialId.replace(/[-_=+\/]/g, '')) {
                newExcluded.push(excluded);
            }
        }
        optionData.excludeCredentials = newExcluded;
        WebAuthnFormHandler.$button.data('credential-options', optionData);
    }
}
WebAuthnFormHandler.$button = $('#attach-webauthn');
new WebAuthnFormHandler();
