<?php

declare(strict_types=1);

namespace CraftCms\Cms\Auth\Passkeys;

use Carbon\CarbonInterface;
use CraftCms\Cms\Auth\Models\WebAuthn;
use CraftCms\Cms\Cms;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Container\Attributes\Scoped;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use ParagonIE\ConstantTime\Base64UrlSafe;
use Throwable;
use Webauthn\AuthenticatorAssertionResponse;
use Webauthn\AuthenticatorAttestationResponse;
use Webauthn\Exception\InvalidUserHandleException;
use Webauthn\PublicKeyCredential;
use Webauthn\PublicKeyCredentialCreationOptions;
use Webauthn\PublicKeyCredentialOptions;
use Webauthn\PublicKeyCredentialRequestOptions;
use Webauthn\PublicKeyCredentialRpEntity;
use Webauthn\PublicKeyCredentialSource;
use Webauthn\PublicKeyCredentialUserEntity;

#[Scoped]
class Passkeys
{
    /**
     * @see webauthnServer()
     */
    private WebauthnServer $webauthnServer;

    public function __construct(
        /**
         * @var string The session variable name used to store passkey credential creation options.
         */
        public readonly string $passkeyCreationOptionsParam = 'pkCredCreationOptions'
    ) {}

    public function hasPasskeys(User $user): bool
    {
        if (! $user->id) {
            return false;
        }

        return WebAuthn::where('userId', $user->id)->exists();
    }

    /**
     * Returns info about the given user’s saved passkeys.
     *
     * @return Collection<array{
     *     credentialName:string,
     *     dateLastUsed:CarbonInterface|null,
     *     uid:string
     * }>
     */
    public function getPasskeys(User $user): Collection
    {
        if (! $user->id) {
            return new Collection;
        }

        return WebAuthn::query()
            ->select(['credentialName', 'dateLastUsed', 'uid'])
            ->where('userId', $user->id)
            ->get()
            ->map(fn (WebAuthn $passkey) => [
                'credentialName' => $passkey->credentialName,
                'dateLastUsed' => $passkey->dateLastUsed,
                'uid' => $passkey->uid,
            ]);
    }

    /**
     * Generates new passkey credential creation options for the given user.
     */
    public function getPasskeyCreationOptions(User $user): PublicKeyCredentialOptions
    {
        $userEntity = $this->passkeyUserEntity($user);
        $credentialRepository = $this->webauthnServer()->getCredentialRepository();

        $excludeCredentials = array_map(
            fn (PublicKeyCredentialSource $credential) => $credential->getPublicKeyCredentialDescriptor(),
            $credentialRepository->findAllForUserEntity($userEntity),
        );

        $publicKeyCredentialCreationOptions = PublicKeyCredentialCreationOptions::create(
            rp: $this->passkeyRpEntity(),
            user: $userEntity,
            challenge: random_bytes(16),
            pubKeyCredParams: $this->webauthnServer()->getPublicKeyCredentialParametersList(),
            authenticatorSelection: $this->webauthnServer()->getPasskeyAuthenticatorSelectionCriteria(),
            attestation: PublicKeyCredentialCreationOptions::ATTESTATION_CONVEYANCE_PREFERENCE_NONE,
            excludeCredentials: $excludeCredentials
        );

        $serializer = $this->webauthnServer()->getSerializer();
        $serializedData = $serializer->serialize($publicKeyCredentialCreationOptions, 'json');
        Session::put($this->passkeyCreationOptionsParam, $serializedData);

        return $publicKeyCredentialCreationOptions;
    }

    /**
     * Verifies a passkey creation response and stores the passkey.
     */
    public function verifyPasskeyCreationResponse(string $credentials, ?string $credentialName = null): bool
    {
        $optionsJson = Session::get($this->passkeyCreationOptionsParam);

        if (! $optionsJson) {
            return false;
        }

        $serializer = $this->webauthnServer()->getSerializer();

        $publicKeyCredentialCreationOptions = $serializer->deserialize(
            $optionsJson,
            PublicKeyCredentialCreationOptions::class,
            'json',
        );
        $publicKeyCredential = $serializer->deserialize(
            $credentials,
            PublicKeyCredential::class,
            'json',
        );
        $authenticatorAttestationResponse = $publicKeyCredential->response;

        if (! $authenticatorAttestationResponse instanceof AuthenticatorAttestationResponse) {
            Log::warning('Authenticator Attestation Response was not of AuthenticatorAttestationResponse type.');

            return false;
        }

        try {
            $publicKeyCredentialSource = $this->webauthnServer()->getAuthenticatorAttestationResponseValidator()->check(
                $authenticatorAttestationResponse,
                $publicKeyCredentialCreationOptions,
                request()->host(),
            );
        } catch (Throwable $e) {
            Log::warning('Authenticator Attestation Response Validation failed: '.$e->getMessage());

            return false;
        }

        $credentialRepository = $this->webauthnServer()->getCredentialRepository();
        $credentialRepository->savedNamedCredentialSource($publicKeyCredentialSource, $credentialName);

        return true;
    }

    /**
     * Returns the public key credential request options.
     */
    public function getPasskeyRequestOptions(): PublicKeyCredentialRequestOptions
    {
        return PublicKeyCredentialRequestOptions::create(
            challenge: random_bytes(32),
            userVerification: PublicKeyCredentialRequestOptions::USER_VERIFICATION_REQUIREMENT_REQUIRED,
        );
    }

    /**
     * Verifies a passkey authentication response and stores the passkey.
     *
     * @param  string  $requestOptions  The public key credential request options
     * @param  string  $response  The authentication response data
     */
    public function verifyPasskey(
        User $user,
        string $requestOptions,
        string $response,
        bool $checkOldUserHandle = false,
    ): bool {
        $serializer = $this->webauthnServer()->getSerializer();

        $requestOptions = $serializer->deserialize(
            $requestOptions,
            PublicKeyCredentialRequestOptions::class,
            'json',
        );

        $userEntity = $this->passkeyUserEntity($user);
        $publicKeyCredential = $serializer->deserialize(
            $response,
            PublicKeyCredential::class,
            'json',
        );
        $authenticatorAssertionResponse = $publicKeyCredential->response;

        if (! $authenticatorAssertionResponse instanceof AuthenticatorAssertionResponse) {
            Log::warning('Authenticator Assertion Response was not of AuthenticatorAssertionResponse type.');

            return false;
        }

        $publicKeyCredentialSource = $this->webauthnServer()->getCredentialRepository()->findOneByCredentialId(
            $publicKeyCredential->rawId,
            $checkOldUserHandle,
        );

        if ($publicKeyCredentialSource === null) {
            Log::warning('No publicKeyCredential source was found.');

            return false;
        }

        try {
            $this->webauthnServer()->getAuthenticatorAssertionResponseValidator()->check(
                $publicKeyCredentialSource,
                $authenticatorAssertionResponse,
                $requestOptions,
                request()->host(),
                $userEntity->id,
            );
        } catch (InvalidUserHandleException $exception) {
            throw $exception;
        } catch (Throwable $e) {
            Log::warning('Authenticator Assertion Response Validation failed: '.$e->getMessage());

            return false;
        }

        return true;
    }

    public function deletePasskey(User $user, string $uid): void
    {
        WebAuthn::where('userId', $user->id)->where('uid', $uid)->delete();
    }

    /**
     * Return WebauthnServer
     */
    public function webauthnServer(): WebauthnServer
    {
        if (! isset($this->webauthnServer)) {
            $this->webauthnServer = new WebauthnServer;
        }

        return $this->webauthnServer;
    }

    /**
     * Returns User Entity for given User element
     */
    public function passkeyUserEntity(User $user): PublicKeyCredentialUserEntity
    {
        return PublicKeyCredentialUserEntity::create(
            name: $user->email,
            id: Base64UrlSafe::encodeUnpadded($user->uid),
            displayName: $user->getName(),
        );
    }

    /**
     * Returns RP Entity (i.e. the application)
     */
    private function passkeyRpEntity(): PublicKeyCredentialRpEntity
    {
        return PublicKeyCredentialRpEntity::create(
            name: Cms::systemName(),
            id: request()->host(),
        );
    }
}
