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
     * Test verifying a passkey registered under webauthn-lib v4, whose user handle is the user’s raw UID,
     * and a passkey registered since, whose user handle is the UID base64url-encoded.
     *
     * @dataProvider verifyPasskeyDataProvider
     * @param bool $rawUserHandle
     */
    public function testVerifyPasskey(bool $rawUserHandle): void
    {
        $userHandle = $rawUserHandle ? $this->user->uid : Base64UrlSafe::encodeUnpadded($this->user->uid);
        $credentialId = random_bytes(16);
        $this->saveCredential($credentialId, $userHandle);

        $requestOptions = PublicKeyCredentialRequestOptions::create(
            challenge: random_bytes(32),
            userVerification: PublicKeyCredentialRequestOptions::USER_VERIFICATION_REQUIREMENT_REQUIRED,
        );
        $serializer = Craft::$app->getAuth()->webauthnServer()->getSerializer();

        self::assertTrue(Craft::$app->getAuth()->verifyPasskey(
            $this->user,
            $serializer->serialize($requestOptions, 'json'),
            $this->assertionResponse($credentialId, $requestOptions->challenge, $userHandle),
        ));
    }

    public static function verifyPasskeyDataProvider(): array
    {
        return [
            'webauthn-lib v4 passkey' => [true],
            'webauthn-lib v5 passkey' => [false],
        ];
    }

    /**
     * Test that a passkey whose user handle doesn’t match the user fails verification rather than throwing.
     */
    public function testVerifyPasskeyWithMismatchedUserHandle(): void
    {
        $credentialId = random_bytes(16);
        $this->saveCredential($credentialId, Base64UrlSafe::encodeUnpadded($this->user->uid));

        $requestOptions = PublicKeyCredentialRequestOptions::create(challenge: random_bytes(32));
        $serializer = Craft::$app->getAuth()->webauthnServer()->getSerializer();

        self::assertFalse(Craft::$app->getAuth()->verifyPasskey(
            $this->user,
            $serializer->serialize($requestOptions, 'json'),
            $this->assertionResponse($credentialId, $requestOptions->challenge, StringHelper::UUID()),
        ));
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
    private function assertionResponse(string $credentialId, string $challenge, string $userHandle): string
    {
        $host = Craft::$app->getRequest()->getHostName();
        $clientData = json_encode([
            'type' => 'webauthn.get',
            'challenge' => Base64UrlSafe::encodeUnpadded($challenge),
            'origin' => "https://$host",
            'crossOrigin' => false,
        ]);
        // The RP ID hash, then the user present and user verified flags, then the signature counter
        $authenticatorData = hash('sha256', $host, true) . "\x05" . pack('N', 1);
        openssl_sign($authenticatorData . hash('sha256', $clientData, true), $signature, $this->privateKey, OPENSSL_ALGO_SHA256);

        return json_encode([
            'id' => Base64UrlSafe::encodeUnpadded($credentialId),
            'rawId' => Base64UrlSafe::encodeUnpadded($credentialId),
            'type' => 'public-key',
            'response' => [
                'clientDataJSON' => Base64UrlSafe::encodeUnpadded($clientData),
                'authenticatorData' => Base64UrlSafe::encodeUnpadded($authenticatorData),
                'signature' => Base64UrlSafe::encodeUnpadded($signature),
                'userHandle' => Base64UrlSafe::encodeUnpadded($userHandle),
            ],
        ]);
    }
}
