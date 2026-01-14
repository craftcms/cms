<?php

declare(strict_types=1);

namespace CraftCms\Cms\Auth\Passkeys;

use craft\helpers\Session as SessionHelper;
use CraftCms\Cms\Auth\Models\WebAuthn;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Support\Json;
use CraftCms\Cms\User\Elements\User;
use GuzzleHttp\Psr7\ServerRequest;
use Illuminate\Container\Attributes\Scoped;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use ParagonIE\ConstantTime\Base64UrlSafe;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;
use Webauthn\AuthenticatorAssertionResponse;
use Webauthn\AuthenticatorAttestationResponse;
use Webauthn\PublicKeyCredential;
use Webauthn\PublicKeyCredentialCreationOptions;
use Webauthn\PublicKeyCredentialOptions;
use Webauthn\PublicKeyCredentialRequestOptions;
use Webauthn\PublicKeyCredentialRpEntity;
use Webauthn\PublicKeyCredentialSource;
use Webauthn\PublicKeyCredentialUserEntity;

#[Scoped]
final class Passkeys
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
     *     dateLastUsed:\Carbon\CarbonInterface|null,
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

        $excludeCredentials = array_map(
            fn (PublicKeyCredentialSource $credential) => $credential->getPublicKeyCredentialDescriptor(),
            (new CredentialRepository)->findAllForUserEntity($userEntity),
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

        SessionHelper::set($this->passkeyCreationOptionsParam, Json::encode($publicKeyCredentialCreationOptions));

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

        $publicKeyCredentialCreationOptions = PublicKeyCredentialCreationOptions::createFromArray(Json::decode($optionsJson));
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

        $credentialRepository = new CredentialRepository;
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
     * @param  PublicKeyCredentialRequestOptions|array|string  $requestOptions  The public key credential request options
     * @param  string  $response  The authentication response data
     */
    public function verifyPasskey(
        User $user,
        PublicKeyCredentialRequestOptions|array|string $requestOptions,
        string $response,
    ): bool {
        if (is_array($requestOptions)) {
            $requestOptions = PublicKeyCredentialRequestOptions::createFromArray($requestOptions);
        } elseif (is_string($requestOptions)) {
            $requestOptions = PublicKeyCredentialRequestOptions::createFromString($requestOptions);
        }

        $userEntity = $this->passkeyUserEntity($user);
        $publicKeyCredential = $this->webauthnServer()->getPublicKeyCredentialLoader()->load($response);
        $authenticatorAssertionResponse = $publicKeyCredential->response;

        if (! $authenticatorAssertionResponse instanceof AuthenticatorAssertionResponse) {
            Log::warning('Authenticator Assertion Response was not of AuthenticatorAssertionResponse type.');

            return false;
        }

        $serverRequest = $this->buildServerRequest(ServerRequest::fromGlobals());
        try {
            $this->webauthnServer()->getAuthenticatorAssertionResponseValidator()->check(
                $publicKeyCredential->rawId,
                $authenticatorAssertionResponse,
                $requestOptions,
                $serverRequest,
                $userEntity->id,
            );
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
    private function webauthnServer(): WebauthnServer
    {
        if (! isset($this->webauthnServer)) {
            $this->webauthnServer = new WebauthnServer;
        }

        return $this->webauthnServer;
    }

    /**
     * Returns User Entity for given User element
     */
    private function passkeyUserEntity(User $user): PublicKeyCredentialUserEntity
    {
        $data = [
            'name' => $user->email,
            'id' => Base64UrlSafe::encodeUnpadded($user->uid),
            'displayName' => $user->getName(),
        ];

        return PublicKeyCredentialUserEntity::createFromArray($data);
    }

    /**
     * Returns RP Entity (i.e. the application)
     */
    private function passkeyRpEntity(): PublicKeyCredentialRpEntity
    {
        return PublicKeyCredentialRpEntity::createFromArray([
            'name' => Cms::systemName(),
            'id' => request()->host(),
        ]);
    }

    /**
     * Builds server request using the Craft-provided data, e.g. host name.
     */
    private function buildServerRequest(ServerRequestInterface $defaultServerRequest): ServerRequestInterface
    {
        $uri = $defaultServerRequest->getUri();
        $uri = $uri->withHost(request()->host());

        $serverRequest = new ServerRequest(
            $defaultServerRequest->getMethod(),
            $uri,
            $defaultServerRequest->getHeaders(),
            $defaultServerRequest->getBody(),
            $defaultServerRequest->getProtocolVersion(),
            $_SERVER
        );

        return $serverRequest
            ->withCookieParams($_COOKIE)
            ->withQueryParams(\Illuminate\Support\Facades\Request::query())
            ->withParsedBody(\Illuminate\Support\Facades\Request::post())
            ->withUploadedFiles(ServerRequest::normalizeFiles($_FILES));
    }
}
