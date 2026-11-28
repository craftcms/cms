<?php

declare(strict_types=1);

namespace CraftCms\Cms\Auth\OAuthServer;

use CraftCms\Cms\Auth\PersonalAccessTokens\PersonalAccessTokens;
use Laravel\Passport\Passport;
use League\OAuth2\Server\CryptKey;
use OpenSSLAsymmetricKey;

/**
 * The public key that verifies the access tokens Craft issues, published as a JSON Web Key Set (RFC 7517) so that
 * other services can verify those tokens.
 *
 * Craft signs access tokens with Passport’s key pair, so the key comes from the `passport.public_key` config setting
 * or Passport’s `oauth-public.key` file. Each access token’s `kid` header names the key that signed it.
 *
 * @since 6.0.0
 */
class JsonWebKeys
{
    public function __construct(private readonly PersonalAccessTokens $tokens) {}

    /**
     * The JWK Set, which is empty when Passport’s keys are missing or invalid.
     *
     * @return array{keys: list<array<string, string>>}
     */
    public function keySet(): array
    {
        if (! $this->tokens->hasKeys()) {
            return ['keys' => []];
        }

        // Resolved the same way as Passport’s resource server key
        $key = str_replace('\\n', "\n", (string) config('passport.public_key'));
        $key = new CryptKey($key ?: 'file://'.Passport::keyPath('oauth-public.key'), null, Passport::$validateKeyPermissions);

        return ['keys' => [self::jwk(openssl_pkey_get_public($key->getKeyContents()))]];
    }

    /**
     * The public JWK for an RSA key.
     *
     * @return array{kty: string, n: string, e: string, alg: string, use: string, kid: string}
     */
    public static function jwk(OpenSSLAsymmetricKey $key): array
    {
        ['n' => $n, 'e' => $e] = self::components($key);

        return [
            'kty' => 'RSA',
            'n' => $n,
            'e' => $e,
            'alg' => 'RS256',
            'use' => 'sig',
            'kid' => self::keyId($key),
        ];
    }

    /** The RFC 7638 thumbprint of an RSA key, which identifies it in the JWK Set and in access token headers. */
    public static function keyId(OpenSSLAsymmetricKey $key): string
    {
        ['n' => $n, 'e' => $e] = self::components($key);

        return self::base64Url(hash('sha256', sprintf('{"e":"%s","kty":"RSA","n":"%s"}', $e, $n), true));
    }

    /** @return array{n: string, e: string} */
    private static function components(OpenSSLAsymmetricKey $key): array
    {
        $rsa = openssl_pkey_get_details($key)['rsa'];

        return ['n' => self::base64Url($rsa['n']), 'e' => self::base64Url($rsa['e'])];
    }

    private static function base64Url(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
