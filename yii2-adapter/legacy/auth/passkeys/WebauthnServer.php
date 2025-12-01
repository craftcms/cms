<?php

namespace craft\auth\passkeys;

use Cose\Algorithm\Manager;
use Cose\Algorithm\Signature\ECDSA\ES256;
use Cose\Algorithm\Signature\ECDSA\ES256K;
use Cose\Algorithm\Signature\ECDSA\ES384;
use Cose\Algorithm\Signature\ECDSA\ES512;
use Cose\Algorithm\Signature\EdDSA\Ed256;
use Cose\Algorithm\Signature\EdDSA\Ed512;
use Cose\Algorithm\Signature\RSA\PS256;
use Cose\Algorithm\Signature\RSA\PS384;
use Cose\Algorithm\Signature\RSA\PS512;
use Cose\Algorithm\Signature\RSA\RS256;
use Cose\Algorithm\Signature\RSA\RS384;
use Cose\Algorithm\Signature\RSA\RS512;
use Cose\Algorithms;
use Symfony\Component\Serializer\SerializerInterface;
use Webauthn\AttestationStatement\AttestationObjectLoader;
use Webauthn\AttestationStatement\AttestationStatementSupportManager;
use Webauthn\AttestationStatement\NoneAttestationStatementSupport;
use Webauthn\AuthenticationExtensions\ExtensionOutputCheckerHandler;
use Webauthn\AuthenticatorAssertionResponseValidator;
use Webauthn\AuthenticatorAttestationResponseValidator;
use Webauthn\AuthenticatorSelectionCriteria;
use Webauthn\Denormalizer\WebauthnSerializerFactory;
use Webauthn\PublicKeyCredentialLoader;
use Webauthn\PublicKeyCredentialParameters;
use Webauthn\TokenBinding\IgnoreTokenBindingHandler;

/**
 * Webauthn server.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.0.0
 * @internal
 */
class WebauthnServer
{
    /**
     * Returns the token binding handler.
     *
     * > At the time of writing, we recommend to ignore this feature.
     *
     * @return IgnoreTokenBindingHandler
     * @see https://webauthn-doc.spomky-labs.com/v/v4.5/pure-php/the-hard-way#token-binding-handler
     */
    public function getTokenBindingHandler(): IgnoreTokenBindingHandler
    {
        return IgnoreTokenBindingHandler::create();
    }

    /**
     * Returns supported attestation statement types.
     *
     * > Note that you should only use the none one unless you have specific needs.
     *
     * @return AttestationStatementSupportManager
     * @see https://webauthn-doc.spomky-labs.com/pure-php/the-hard-way#supported-attestation-statement-types
     */
    public function getAttestationStatementManager(): AttestationStatementSupportManager
    {
        // The manager will receive data to load and select the appropriate
        $attestationStatementSupportManager = AttestationStatementSupportManager::create();
        $attestationStatementSupportManager->add(NoneAttestationStatementSupport::create());

        return $attestationStatementSupportManager;
    }

    /**
     * Returns the object that will load the Attestation statements received from the devices.
     *
     * @return AttestationObjectLoader
     * @see https://webauthn-doc.spomky-labs.com/pure-php/the-hard-way#attestation-object-loader
     */
    public function getAttestationObjectLoader(): AttestationObjectLoader
    {
        return AttestationObjectLoader::create(
            $this->getAttestationStatementManager()
        );
    }

    /**
     * Returns the object that will load the Public Key.
     *
     * @return PublicKeyCredentialLoader
     * @see https://webauthn-doc.spomky-labs.com/pure-php/the-hard-way#public-key-credential-loader
     */
    public function getPublicKeyCredentialLoader(): PublicKeyCredentialLoader
    {
        return PublicKeyCredentialLoader::create(
            $this->getAttestationObjectLoader()
        );
    }

    /**
     * Return the Symphony Serializer that will deal with serialization/deserialization of data.
     *
     * @return SerializerInterface
     * @see https://webauthn-doc.spomky-labs.com/v/v4.8/pure-php/input-loading#the-serializer
     */
    public function getSerializer(): SerializerInterface
    {
        $attestationStatementSupportManager = AttestationStatementSupportManager::create();
        $attestationStatementSupportManager->add(NoneAttestationStatementSupport::create());
        $factory = new WebauthnSerializerFactory($attestationStatementSupportManager);

        return $factory->create();
    }

    /**
     * Returns the object that deals with extensions.
     *
     * @return ExtensionOutputCheckerHandler
     * @see https://webauthn-doc.spomky-labs.com/pure-php/the-hard-way#extension-output-checker-handler
     */
    public function getExtensionOutputCheckerHandler(): ExtensionOutputCheckerHandler
    {
        return ExtensionOutputCheckerHandler::create();
    }

    /**
     * Returns a list of cryptographic algorithms to perform data verification based on cryptographic signatures.
     *
     * @return Manager
     * @see https://webauthn-doc.spomky-labs.com/pure-php/the-hard-way#algorithm-manager
     */
    public function getAlgorithmManager(): Manager
    {
        return Manager::create()
            ->add(
                ES256::create(),
                ES256K::create(),
                ES384::create(),
                ES512::create(),

                RS256::create(),
                RS384::create(),
                RS512::create(),

                PS256::create(),
                PS384::create(),
                PS512::create(),

                Ed256::create(),
                Ed512::create(),
            );
    }

    /**
     * Returns the object that will be used to validate the Attestation Responses.
     *
     * @return AuthenticatorAttestationResponseValidator
     * @see https://webauthn-doc.spomky-labs.com/pure-php/the-hard-way#authenticator-attestation-response-validator
     */
    public function getAuthenticatorAttestationResponseValidator(): AuthenticatorAttestationResponseValidator
    {
        return AuthenticatorAttestationResponseValidator::create(
            $this->getAttestationStatementManager(),
            new CredentialRepository(),
            $this->getTokenBindingHandler(),
            $this->getExtensionOutputCheckerHandler(),
        );
    }

    /**
     * Returns the object that will be used to validate the Assertion Responses.
     *
     * @return AuthenticatorAssertionResponseValidator
     * @see https://webauthn-doc.spomky-labs.com/pure-php/the-hard-way#authenticator-assertion-response-validator
     */
    public function getAuthenticatorAssertionResponseValidator(): AuthenticatorAssertionResponseValidator
    {
        return AuthenticatorAssertionResponseValidator::create(
            new CredentialRepository(),
            $this->getTokenBindingHandler(),
            $this->getExtensionOutputCheckerHandler(),
            $this->getAlgorithmManager(),
        );
    }

    /**
     * COSE algorithms that the authenticators must use in the order of interest.
     *
     * @return array
     * @see: https://webauthn-doc.spomky-labs.com/pure-php/authenticator-registration
     */
    public function getPublicKeyCredentialParametersList(): array
    {
        return [
            PublicKeyCredentialParameters::create('public-key', Algorithms::COSE_ALGORITHM_ES256K),
            PublicKeyCredentialParameters::create('public-key', Algorithms::COSE_ALGORITHM_ES256),
            PublicKeyCredentialParameters::create('public-key', Algorithms::COSE_ALGORITHM_RS256),
            PublicKeyCredentialParameters::create('public-key', Algorithms::COSE_ALGORITHM_PS256),
            PublicKeyCredentialParameters::create('public-key', Algorithms::COSE_ALGORITHM_ED256),
        ];
    }

    /**
     * Returns the object containing Authenticator Selection Criteria
     *
     * @return AuthenticatorSelectionCriteria
     */
    public function getPasskeyAuthenticatorSelectionCriteria(): AuthenticatorSelectionCriteria
    {
        return new AuthenticatorSelectionCriteria(
            authenticatorAttachment: null,
            userVerification: AuthenticatorSelectionCriteria::USER_VERIFICATION_REQUIREMENT_REQUIRED,
            residentKey: AuthenticatorSelectionCriteria::RESIDENT_KEY_REQUIREMENT_REQUIRED,
            requireResidentKey: true,
        );
    }
}
