<?php

declare(strict_types=1);

namespace CraftCms\Cms\Auth\Passkeys;

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
use Webauthn\AttestationStatement\AttestationStatementSupportManager;
use Webauthn\AttestationStatement\NoneAttestationStatementSupport;
use Webauthn\AuthenticationExtensions\ExtensionOutputCheckerHandler;
use Webauthn\AuthenticatorAssertionResponseValidator;
use Webauthn\AuthenticatorAttestationResponseValidator;
use Webauthn\AuthenticatorSelectionCriteria;
use Webauthn\CeremonyStep\CeremonyStepManagerFactory;
use Webauthn\Denormalizer\WebauthnSerializerFactory;
use Webauthn\PublicKeyCredentialParameters;

/**
 * @internal
 */
class WebauthnServer
{
    private readonly CeremonyStepManagerFactory $csmFactory;

    private ?SerializerInterface $serializer = null;

    private ?CredentialRepository $credentialRepository = null;

    public function __construct()
    {
        $this->csmFactory = new CeremonyStepManagerFactory;
        $this->csmFactory->setAlgorithmManager($this->getAlgorithmManager());
        $this->csmFactory->setExtensionOutputCheckerHandler($this->getExtensionOutputCheckerHandler());
    }

    /**
     * Returns supported attestation statement types.
     *
     * > Note that you should only use the none one unless you have specific needs.
     *
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
     * Returns the credential repository.
     */
    public function getCredentialRepository(): CredentialRepository
    {
        $this->credentialRepository ??= app(CredentialRepository::class);

        return $this->credentialRepository;
    }

    /**
     * Return the Symphony Serializer that will deal with serialization/deserialization of data.
     *
     * @see https://webauthn-doc.spomky-labs.com/v/v4.8/pure-php/input-loading#the-serializer
     */
    public function getSerializer(): SerializerInterface
    {
        if (isset($this->serializer)) {
            return $this->serializer;
        }

        $attestationStatementSupportManager = AttestationStatementSupportManager::create();
        $attestationStatementSupportManager->add(NoneAttestationStatementSupport::create());

        return $this->serializer = new WebauthnSerializerFactory($attestationStatementSupportManager)->create();
    }

    /**
     * Returns the object that deals with extensions.
     *
     * @see https://webauthn-doc.spomky-labs.com/pure-php/the-hard-way#extension-output-checker-handler
     */
    public function getExtensionOutputCheckerHandler(): ExtensionOutputCheckerHandler
    {
        return ExtensionOutputCheckerHandler::create();
    }

    /**
     * Returns a list of cryptographic algorithms to perform data verification based on cryptographic signatures.
     *
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
     * @see https://webauthn-doc.spomky-labs.com/pure-php/the-hard-way#authenticator-attestation-response-validator
     */
    public function getAuthenticatorAttestationResponseValidator(): AuthenticatorAttestationResponseValidator
    {
        return AuthenticatorAttestationResponseValidator::create(ceremonyStepManager: $this->csmFactory->creationCeremony());
    }

    /**
     * Returns the object that will be used to validate the Assertion Responses.
     *
     * @see https://webauthn-doc.spomky-labs.com/pure-php/the-hard-way#authenticator-assertion-response-validator
     */
    public function getAuthenticatorAssertionResponseValidator(): AuthenticatorAssertionResponseValidator
    {
        return AuthenticatorAssertionResponseValidator::create(ceremonyStepManager: $this->csmFactory->requestCeremony());
    }

    /**
     * COSE algorithms that the authenticators must use in the order of interest.
     *
     * @see: https://webauthn-doc.spomky-labs.com/pure-php/authenticator-registration
     *
     * @return list<PublicKeyCredentialParameters>
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
     */
    public function getPasskeyAuthenticatorSelectionCriteria(): AuthenticatorSelectionCriteria
    {
        return new AuthenticatorSelectionCriteria(
            userVerification: AuthenticatorSelectionCriteria::USER_VERIFICATION_REQUIREMENT_REQUIRED,
            residentKey: AuthenticatorSelectionCriteria::RESIDENT_KEY_REQUIREMENT_REQUIRED,
        );
    }
}
