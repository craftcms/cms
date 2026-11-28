<?php

declare(strict_types=1);

namespace CraftCms\Cms\Auth\OAuthServer;

use DateTimeImmutable;
use Laravel\Passport\Bridge\AccessToken as PassportAccessToken;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use League\OAuth2\Server\CryptKeyInterface;
use League\OAuth2\Server\Entities\ScopeEntityInterface;
use Override;
use SensitiveParameter;

/**
 * A Passport access token with two additions that let services outside Craft verify it:
 *
 * - A `kid` header with the RFC 7638 thumbprint of its signing key, matching that key’s entry in the {@see JsonWebKeys}
 *   set. Verifiers use it to pick the key from the set (RFC 7517 §4.5), and JWT libraries won’t verify against a key
 *   set without it.
 * - An `iss` claim with the issuer of the {@see OAuthResource} its scopes belong to. Verifiers fetch that issuer’s
 *   authorization server metadata, check that its `issuer` exactly matches `iss`, and find the key set at its
 *   `jwks_uri` (RFC 9068 §4).
 *
 * The rest of the token is what league/oauth2-server builds. Passport reads the client ID from `aud` and the scopes
 * from `scopes`, so the token doesn’t follow the rest of the RFC 9068 profile, which expects `aud` to name the API.
 *
 * @see https://www.rfc-editor.org/rfc/rfc7517.html#section-4.5
 * @see https://www.rfc-editor.org/rfc/rfc9068.html#section-4
 * @since 6.0.0
 */
class AccessToken extends PassportAccessToken
{
    private CryptKeyInterface $signingKey;

    #[Override]
    public function setPrivateKey(#[SensitiveParameter] CryptKeyInterface $privateKey): void
    {
        parent::setPrivateKey($privateKey);
        $this->signingKey = $privateKey;
    }

    /** Builds the same JWT as league/oauth2-server’s `AccessTokenTrait`, plus the `kid` header and `iss` claim. */
    #[Override]
    public function toString(): string
    {
        $privateKey = openssl_pkey_get_private($this->signingKey->getKeyContents(), $this->signingKey->getPassPhrase());
        $config = Configuration::forAsymmetricSigner(
            new Sha256,
            InMemory::plainText($this->signingKey->getKeyContents(), $this->signingKey->getPassPhrase() ?? ''),
            InMemory::plainText('empty', 'empty'),
        );

        $builder = $config->builder()->withHeader('kid', JsonWebKeys::keyId($privateKey));
        $resource = app(OAuthResources::class)->forScopes(array_map(
            fn (ScopeEntityInterface $scope): string => $scope->getIdentifier(),
            $this->getScopes(),
        ));

        if ($resource) {
            $builder = $builder->issuedBy($resource->issuer());
        }

        return $builder
            ->permittedFor($this->getClient()->getIdentifier())
            ->identifiedBy($this->getIdentifier())
            ->issuedAt(new DateTimeImmutable)
            ->canOnlyBeUsedAfter(new DateTimeImmutable)
            ->expiresAt($this->getExpiryDateTime())
            ->relatedTo($this->getUserIdentifier() ?? $this->getClient()->getIdentifier())
            ->withClaim('scopes', $this->getScopes())
            ->getToken($config->signer(), $config->signingKey())
            ->toString();
    }
}
