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
 * A Passport access token whose JWT has a `kid` header naming its signing key in the {@see JsonWebKeys} set, and an
 * `iss` claim with the issuer of the {@see OAuthResource} its scopes belong to. That issuer’s metadata lists the key set
 * as its `jwks_uri`.
 *
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
