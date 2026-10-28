<?php

declare(strict_types=1);

use CraftCms\Cms\Auth\Models\WebAuthn;
use CraftCms\Cms\Auth\Passkeys\Passkeys;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\User\Models\User as UserModel;
use ParagonIE\ConstantTime\Base64UrlSafe;
use Symfony\Component\Uid\Uuid;
use Webauthn\CredentialRecord;
use Webauthn\PublicKeyCredentialRequestOptions;
use Webauthn\TrustPath\EmptyTrustPath;

beforeEach(function () {
    $this->passkeys = app(Passkeys::class);
    $this->user = UserModel::factory()->active()->createElement();
    $this->counter = 0;

    $this->privateKey = openssl_pkey_new([
        'curve_name' => 'prime256v1',
        'private_key_type' => OPENSSL_KEYTYPE_EC,
    ]);
});

function passkeyUserHandle(User $user, bool $raw): string
{
    return $raw ? $user->uid : Base64UrlSafe::encodeUnpadded($user->uid);
}

function savePasskeyCredential(User $user, string $credentialId, string $userHandle): void
{
    $details = openssl_pkey_get_details(test()->privateKey);
    $x = str_pad($details['ec']['x'], 32, "\0", STR_PAD_LEFT);
    $y = str_pad($details['ec']['y'], 32, "\0", STR_PAD_LEFT);

    $credentialRecord = CredentialRecord::create(
        publicKeyCredentialId: $credentialId,
        type: 'public-key',
        transports: [],
        attestationType: 'none',
        trustPath: EmptyTrustPath::create(),
        aaguid: Uuid::fromString('00000000-0000-0000-0000-000000000000'),
        // CBOR-encoded COSE EC2 key: {1: 2 (EC2), 3: -7 (ES256), -1: 1 (P-256), -2: x, -3: y}
        credentialPublicKey: "\xA5\x01\x02\x03\x26\x20\x01\x21\x58\x20".$x."\x22\x58\x20".$y,
        userHandle: $userHandle,
        counter: 0,
    );

    WebAuthn::factory()->create([
        'userId' => $user->id,
        'credentialName' => 'Test passkey',
        'credentialId' => Base64UrlSafe::encodeUnpadded($credentialId),
        'credential' => test()->passkeys->webauthnServer()->getSerializer()->serialize($credentialRecord, 'json'),
    ]);
}

/**
 * Runs a real assertion ceremony, as an authenticator holding `$responseUserHandle` would perform it.
 */
function verifyPasskeyAssertion(User $user, string $credentialId, ?string $responseUserHandle): CredentialRecord|false
{
    $requestOptions = PublicKeyCredentialRequestOptions::create(
        challenge: random_bytes(32),
        userVerification: PublicKeyCredentialRequestOptions::USER_VERIFICATION_REQUIREMENT_REQUIRED,
    );

    $host = request()->host();
    $clientData = json_encode([
        'type' => 'webauthn.get',
        'challenge' => Base64UrlSafe::encodeUnpadded($requestOptions->challenge),
        'origin' => "https://$host",
        'crossOrigin' => false,
    ]);
    // The RP ID hash, then the user present and user verified flags, then the signature counter
    $authenticatorData = hash('sha256', $host, true)."\x05".pack('N', ++test()->counter);
    openssl_sign($authenticatorData.hash('sha256', $clientData, true), $signature, test()->privateKey, OPENSSL_ALGO_SHA256);

    $response = json_encode([
        'id' => Base64UrlSafe::encodeUnpadded($credentialId),
        'rawId' => Base64UrlSafe::encodeUnpadded($credentialId),
        'type' => 'public-key',
        'response' => [
            'clientDataJSON' => Base64UrlSafe::encodeUnpadded($clientData),
            'authenticatorData' => Base64UrlSafe::encodeUnpadded($authenticatorData),
            'signature' => Base64UrlSafe::encodeUnpadded($signature),
        ] + ($responseUserHandle !== null ? ['userHandle' => Base64UrlSafe::encodeUnpadded($responseUserHandle)] : []),
    ]);

    return test()->passkeys->verifyPasskey(
        $user,
        test()->passkeys->webauthnServer()->getSerializer()->serialize($requestOptions, 'json'),
        $response,
    );
}

it('verifies passkeys whose credential and authenticator hold either form of the user handle', function (bool $rawCredentialUserHandle, ?bool $rawResponseUserHandle) {
    $credentialId = random_bytes(16);
    savePasskeyCredential($this->user, $credentialId, passkeyUserHandle($this->user, $rawCredentialUserHandle));
    $responseUserHandle = $rawResponseUserHandle !== null ? passkeyUserHandle($this->user, $rawResponseUserHandle) : null;

    expect(verifyPasskeyAssertion($this->user, $credentialId, $responseUserHandle))->toBeInstanceOf(CredentialRecord::class);

    // The credential should now hold the same user handle as the authenticator
    $credentialRecord = $this->passkeys->webauthnServer()->getCredentialRepository()->findOneByCredentialId($credentialId);
    expect($credentialRecord->userHandle)->toBe($responseUserHandle ?? passkeyUserHandle($this->user, $rawCredentialUserHandle));

    // And verifying it again should still work
    expect(verifyPasskeyAssertion($this->user, $credentialId, $responseUserHandle))->toBeInstanceOf(CredentialRecord::class);
})->with([
    'webauthn-lib v4 credential, encoded authenticator handle' => [true, false],
    'webauthn-lib v4 credential, raw authenticator handle' => [true, true],
    'webauthn-lib v4 credential, no authenticator handle' => [true, null],
    'webauthn-lib v5 credential, encoded authenticator handle' => [false, false],
    'webauthn-lib v5 credential, raw authenticator handle' => [false, true],
    'webauthn-lib v5 credential, no authenticator handle' => [false, null],
]);

it('fails verification rather than throwing when the authenticator’s user handle doesn’t match', function () {
    $credentialId = random_bytes(16);
    savePasskeyCredential($this->user, $credentialId, passkeyUserHandle($this->user, false));

    expect(verifyPasskeyAssertion($this->user, $credentialId, Str::uuid()->toString()))->toBeFalse();
});

it('fails verification when the passkey’s user handle belongs to a different user', function (bool $rawUserHandle) {
    $otherUser = UserModel::factory()->active()->createElement();

    $credentialId = random_bytes(16);
    $userHandle = passkeyUserHandle($otherUser, $rawUserHandle);
    savePasskeyCredential($this->user, $credentialId, $userHandle);

    expect(verifyPasskeyAssertion($this->user, $credentialId, $userHandle))->toBeFalse();
})->with([
    'raw user handle' => [true],
    'encoded user handle' => [false],
]);
