<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\services;

use Craft;
use craft\elements\User;
use craft\helpers\StringHelper;
use craft\records\WebAuthn;
use craft\test\TestCase;
use ParagonIE\ConstantTime\Base64UrlSafe;
use Symfony\Component\Uid\Uuid;
use Webauthn\CredentialRecord;
use Webauthn\PublicKeyCredentialRequestOptions;
use Webauthn\TrustPath\EmptyTrustPath;

/**
 * Unit tests for the auth service
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 */
class AuthTest extends TestCase
{
    private User $user;

    /**
     * @var \OpenSSLAsymmetricKey The passkey’s private key
     */
    private mixed $privateKey;

    /**
     * @var int The passkey’s signature counter
     */
    private int $counter = 0;

    protected function _before(): void
    {
        parent::_before();

        $this->user = new User([
            'username' => 'passkey-' . StringHelper::randomString(6),
            'email' => 'passkey-' . StringHelper::randomString(6) . '@example.com',
        ]);
        Craft::$app->getElements()->saveElement($this->user, false);

        $this->privateKey = openssl_pkey_new([
            'curve_name' => 'prime256v1',
            'private_key_type' => OPENSSL_KEYTYPE_EC,
        ]);
    }

    /**
     * Test verifying passkeys whose stored credential and authenticator each hold either the user’s raw UID
     * or the base64url-encoded UID as their user handle.
     *
     * @dataProvider verifyPasskeyDataProvider
     * @param bool $rawCredentialUserHandle
     * @param bool|null $rawResponseUserHandle
     */
    public function testVerifyPasskey(bool $rawCredentialUserHandle, ?bool $rawResponseUserHandle): void
    {
        $credentialId = random_bytes(16);
        $this->saveCredential($credentialId, $this->userHandle($this->user, $rawCredentialUserHandle));
        $responseUserHandle = $rawResponseUserHandle !== null ? $this->userHandle($this->user, $rawResponseUserHandle) : null;

        self::assertTrue($this->verifyPasskey($this->user, $credentialId, $responseUserHandle));

        // the credential should now hold the same user handle as the authenticator
        $credentialRecord = Craft::$app->getAuth()->webauthnServer()->getCredentialRepository()->findOneByCredentialId($credentialId);
        self::assertSame(
            $responseUserHandle ?? $this->userHandle($this->user, $rawCredentialUserHandle),
            $credentialRecord->userHandle,
        );

        // and verifying it again should still work
        self::assertTrue($this->verifyPasskey($this->user, $credentialId, $responseUserHandle));
    }

    public static function verifyPasskeyDataProvider(): array
    {
        return [
            'webauthn-lib v4 credential, encoded authenticator handle' => [true, false],
            'webauthn-lib v4 credential, raw authenticator handle' => [true, true],
            'webauthn-lib v4 credential, no authenticator handle' => [true, null],
            'webauthn-lib v5 credential, encoded authenticator handle' => [false, false],
            'webauthn-lib v5 credential, raw authenticator handle' => [false, true],
            'webauthn-lib v5 credential, no authenticator handle' => [false, null],
        ];
    }

    /**
     * Test that a passkey whose authenticator user handle doesn’t match the user fails verification rather than throwing.
     */
    public function testVerifyPasskeyWithMismatchedUserHandle(): void
    {
        $credentialId = random_bytes(16);
        $this->saveCredential($credentialId, $this->userHandle($this->user, false));

        self::assertFalse($this->verifyPasskey($this->user, $credentialId, StringHelper::UUID()));
    }

    /**
     * Test that a passkey whose user handle belongs to a different user fails verification.
     *
     * @dataProvider verifyPasskeyForOtherUserDataProvider
     * @param bool $rawUserHandle
     */
    public function testVerifyPasskeyForOtherUser(bool $rawUserHandle): void
    {
        $otherUser = new User([
            'username' => 'passkey-' . StringHelper::randomString(6),
            'email' => 'passkey-' . StringHelper::randomString(6) . '@example.com',
        ]);
        Craft::$app->getElements()->saveElement($otherUser, false);

        $credentialId = random_bytes(16);
        $userHandle = $this->userHandle($otherUser, $rawUserHandle);
        $this->saveCredential($credentialId, $userHandle);

        self::assertFalse($this->verifyPasskey($this->user, $credentialId, $userHandle));
    }

    public static function verifyPasskeyForOtherUserDataProvider(): array
    {
        return [
            'raw user handle' => [true],
            'encoded user handle' => [false],
        ];
    }

    private function userHandle(User $user, bool $raw): string
    {
        return $raw ? $user->uid : Base64UrlSafe::encodeUnpadded($user->uid);
    }

    private function verifyPasskey(User $user, string $credentialId, ?string $responseUserHandle): bool
    {
        $requestOptions = PublicKeyCredentialRequestOptions::create(
            challenge: random_bytes(32),
            userVerification: PublicKeyCredentialRequestOptions::USER_VERIFICATION_REQUIREMENT_REQUIRED,
        );
        $serializer = Craft::$app->getAuth()->webauthnServer()->getSerializer();

        return Craft::$app->getAuth()->verifyPasskey(
            $user,
            $serializer->serialize($requestOptions, 'json'),
            $this->assertionResponse($credentialId, $requestOptions->challenge, $responseUserHandle),
        );
    }

    private function saveCredential(string $credentialId, string $userHandle): void
    {
        $credentialRecord = CredentialRecord::create(
            publicKeyCredentialId: $credentialId,
            type: 'public-key',
            transports: [],
            attestationType: 'none',
            trustPath: EmptyTrustPath::create(),
            aaguid: Uuid::fromString('00000000-0000-0000-0000-000000000000'),
            credentialPublicKey: $this->coseKey(),
            userHandle: $userHandle,
            counter: 0,
        );

        $record = new WebAuthn();
        $record->userId = $this->user->id;
        $record->credentialName = 'Test passkey';
        $record->credentialId = Base64UrlSafe::encodeUnpadded($credentialId);
        $record->credential = Craft::$app->getAuth()->webauthnServer()->getSerializer()->serialize($credentialRecord, 'json');
        $record->save();
    }

    /**
     * Returns the passkey’s public key as a CBOR-encoded COSE EC2 key.
     */
    private function coseKey(): string
    {
        $details = openssl_pkey_get_details($this->privateKey);
        $x = str_pad($details['ec']['x'], 32, "\0", STR_PAD_LEFT);
        $y = str_pad($details['ec']['y'], 32, "\0", STR_PAD_LEFT);

        // {1: 2 (EC2), 3: -7 (ES256), -1: 1 (P-256), -2: x, -3: y}
        return "\xA5\x01\x02\x03\x26\x20\x01\x21\x58\x20" . $x . "\x22\x58\x20" . $y;
    }

    /**
     * Returns a signed assertion response, as an authenticator holding `$userHandle` would send it.
     */
    private function assertionResponse(string $credentialId, string $challenge, ?string $userHandle): string
    {
        $host = Craft::$app->getRequest()->getHostName();
        $clientData = json_encode([
            'type' => 'webauthn.get',
            'challenge' => Base64UrlSafe::encodeUnpadded($challenge),
            'origin' => "https://$host",
            'crossOrigin' => false,
        ]);
        // The RP ID hash, then the user present and user verified flags, then the signature counter
        $authenticatorData = hash('sha256', $host, true) . "\x05" . pack('N', ++$this->counter);
        openssl_sign($authenticatorData . hash('sha256', $clientData, true), $signature, $this->privateKey, OPENSSL_ALGO_SHA256);

        return json_encode([
            'id' => Base64UrlSafe::encodeUnpadded($credentialId),
            'rawId' => Base64UrlSafe::encodeUnpadded($credentialId),
            'type' => 'public-key',
            'response' => [
                'clientDataJSON' => Base64UrlSafe::encodeUnpadded($clientData),
                'authenticatorData' => Base64UrlSafe::encodeUnpadded($authenticatorData),
                'signature' => Base64UrlSafe::encodeUnpadded($signature),
            ] + ($userHandle !== null ? ['userHandle' => Base64UrlSafe::encodeUnpadded($userHandle)] : []),
        ]);
    }
}
