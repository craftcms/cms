<?php

namespace craft\oauth;

use Craft;
use craft\db\Query;
use craft\db\Table;
use craft\helpers\Db;
use DateTime;
use League\OAuth2\Server\Entities\AccessTokenEntityInterface;
use League\OAuth2\Server\Entities\AuthCodeEntityInterface;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Entities\RefreshTokenEntityInterface;
use League\OAuth2\Server\Entities\ScopeEntityInterface;
use League\OAuth2\Server\Exception\OAuthServerException;
use League\OAuth2\Server\Exception\UniqueTokenIdentifierConstraintViolationException;
use League\OAuth2\Server\Repositories\AccessTokenRepositoryInterface;
use League\OAuth2\Server\Repositories\AuthCodeRepositoryInterface;
use League\OAuth2\Server\Repositories\ClientRepositoryInterface;
use League\OAuth2\Server\Repositories\RefreshTokenRepositoryInterface;
use League\OAuth2\Server\Repositories\ScopeRepositoryInterface;
use yii\db\IntegrityException;

/**
 * OAuth client and credential storage for a resource. Rows are shared by all resources, as in Laravel Passport: a client
 * belongs to the resources its `scopes` cover, and a credential to the resource that owns its scopes.
 */
class Repositories implements ClientRepositoryInterface, AccessTokenRepositoryInterface, AuthCodeRepositoryInterface, RefreshTokenRepositoryInterface, ScopeRepositoryInterface
{
    public function __construct(private Resource $resource)
    {
    }

    public function getClientEntity(string $clientIdentifier): ?ClientEntityInterface
    {
        $row = (new Query())->from(Table::OAUTH_CLIENTS)->where(['id' => $clientIdentifier, 'revoked' => false])->one();
        if (!$row) {
            return null;
        }

        // Like Passport, a client without `scopes` may request any scope. Otherwise it belongs to the resources whose scopes it lists.
        $client = new Client($row);
        if ($client->scopes !== null && !array_intersect($client->scopes, array_keys($this->resource->scopes))) {
            return null;
        }
        return $client;
    }

    public function validateClient(string $clientIdentifier, ?string $clientSecret, ?string $grantType): bool
    {
        return in_array($grantType, ['authorization_code', 'refresh_token'], true) && $this->getClientEntity($clientIdentifier) !== null;
    }

    public function getScopeEntityByIdentifier(string $identifier): ?ScopeEntityInterface
    {
        if (!isset($this->resource->scopes[$identifier])) {
            return null;
        }

        $scope = new Scope();
        $scope->setIdentifier($identifier);
        return $scope;
    }

    public function finalizeScopes(array $scopes, string $grantType, ClientEntityInterface $clientEntity, ?string $userIdentifier = null, ?string $authCodeId = null): array
    {
        if (!$scopes) {
            throw OAuthServerException::invalidScope('(empty)');
        }

        $client = $this->requireClient($clientEntity->getIdentifier());
        foreach ($scopes as $scope) {
            if (!isset($this->resource->scopes[$scope->getIdentifier()]) || !$client->hasScope($scope->getIdentifier())) {
                throw OAuthServerException::invalidScope($scope->getIdentifier());
            }
        }

        if ($userIdentifier === null) {
            throw OAuthServerException::accessDenied('An authenticated user is required.');
        }

        $user = Craft::$app->get('oauth')->authorizedUser($userIdentifier, $this->resource->id);

        // Drop scopes the user may no longer be granted, such as admin access after losing admin status.
        $allowed = $this->resource->scopesForUser($user, array_map(fn(ScopeEntityInterface $scope) => $scope->getIdentifier(), $scopes));
        $scopes = array_values(array_filter($scopes, fn(ScopeEntityInterface $scope) => in_array($scope->getIdentifier(), $allowed, true)));
        if (!$scopes) {
            throw OAuthServerException::invalidScope('(empty)');
        }

        return $scopes;
    }

    public function getNewToken(ClientEntityInterface $clientEntity, array $scopes, ?string $userIdentifier = null): AccessTokenEntityInterface
    {
        $token = new AccessToken();
        $token->setClient($clientEntity);
        $token->setUserIdentifier($userIdentifier);
        foreach ($scopes as $scope) {
            $token->addScope($scope);
        }
        return $token;
    }

    public function persistNewAccessToken(AccessTokenEntityInterface $accessTokenEntity): void
    {
        $this->requireClient($accessTokenEntity->getClient()->getIdentifier());

        $now = Db::prepareDateForDb(new DateTime());
        $this->persist(Table::OAUTH_ACCESS_TOKENS, [
            'id' => $accessTokenEntity->getIdentifier(),
            'client_id' => $accessTokenEntity->getClient()->getIdentifier(),
            'user_id' => $accessTokenEntity->getUserIdentifier(),
            'name' => $accessTokenEntity instanceof AccessToken ? $accessTokenEntity->name : null,
            'scopes' => $this->encodeScopes($accessTokenEntity->getScopes()),
            'created_at' => $now,
            'updated_at' => $now,
            'expires_at' => Db::prepareDateForDb($accessTokenEntity->getExpiryDateTime()),
        ]);
    }

    public function revokeAccessToken(string $tokenId): void
    {
        $this->revoke(Table::OAUTH_ACCESS_TOKENS, $tokenId);
    }

    public function isAccessTokenRevoked(string $tokenId): bool
    {
        return $this->isRevoked(Table::OAUTH_ACCESS_TOKENS, $tokenId);
    }

    public function getNewAuthCode(): AuthCodeEntityInterface
    {
        return new AuthCode();
    }

    public function persistNewAuthCode(AuthCodeEntityInterface $authCodeEntity): void
    {
        $this->requireClient($authCodeEntity->getClient()->getIdentifier());

        $this->persist(Table::OAUTH_AUTH_CODES, [
            'id' => $authCodeEntity->getIdentifier(),
            'client_id' => $authCodeEntity->getClient()->getIdentifier(),
            'user_id' => $authCodeEntity->getUserIdentifier(),
            'scopes' => $this->encodeScopes($authCodeEntity->getScopes()),
            'expires_at' => Db::prepareDateForDb($authCodeEntity->getExpiryDateTime()),
        ]);
    }

    public function revokeAuthCode(string $codeId): void
    {
        $this->revoke(Table::OAUTH_AUTH_CODES, $codeId);
    }

    public function isAuthCodeRevoked(string $codeId): bool
    {
        return $this->isRevoked(Table::OAUTH_AUTH_CODES, $codeId);
    }

    public function getNewRefreshToken(): ?RefreshTokenEntityInterface
    {
        return new RefreshToken();
    }

    public function persistNewRefreshToken(RefreshTokenEntityInterface $refreshTokenEntity): void
    {
        $accessTokenId = $refreshTokenEntity->getAccessToken()->getIdentifier();
        $accessToken = (new Query())->from(Table::OAUTH_ACCESS_TOKENS)->where(['id' => $accessTokenId])->one();
        if (!$accessToken || !$this->hasResourceScopes($accessToken['scopes'])) {
            throw OAuthServerException::accessDenied('The access token does not belong to this resource.');
        }

        $this->requireClient($accessToken['client_id']);

        $this->persist(Table::OAUTH_REFRESH_TOKENS, [
            'id' => $refreshTokenEntity->getIdentifier(),
            'access_token_id' => $accessTokenId,
            'expires_at' => Db::prepareDateForDb($refreshTokenEntity->getExpiryDateTime()),
        ]);
    }

    public function revokeRefreshToken(string $tokenId): void
    {
        $row = $this->refreshToken($tokenId);
        if ($row && $this->hasResourceScopes($row['scopes'])) {
            Craft::$app->getDb()->createCommand()->update(Table::OAUTH_REFRESH_TOKENS, ['revoked' => true], ['id' => $tokenId])->execute();
        }
    }

    public function isRefreshTokenRevoked(string $tokenId): bool
    {
        $row = $this->refreshToken($tokenId);
        return !$row || $row['revoked'] || !$this->hasResourceScopes($row['scopes']) || !$this->getClientEntity($row['client_id']);
    }

    private function persist(string $table, array $row): void
    {
        try {
            Craft::$app->getDb()->createCommand()->insert($table, $row + ['revoked' => false])->execute();
        } catch (IntegrityException $e) {
            throw UniqueTokenIdentifierConstraintViolationException::create();
        }
    }

    private function revoke(string $table, string $id): void
    {
        $row = (new Query())->select(['scopes'])->from($table)->where(['id' => $id])->one();
        if ($row && $this->hasResourceScopes($row['scopes'])) {
            Craft::$app->getDb()->createCommand()->update($table, ['revoked' => true], ['id' => $id])->execute();
        }
    }

    private function isRevoked(string $table, string $id): bool
    {
        // Access tokens and auth codes belong to the resources that own their scopes.
        $row = (new Query())->from($table)->where(['id' => $id])->one();
        return !$row || $row['revoked'] || !$this->hasResourceScopes($row['scopes']) || !$this->getClientEntity($row['client_id']);
    }

    /**
     * @param ScopeEntityInterface[] $scopes
     */
    private function encodeScopes(array $scopes): string
    {
        return json_encode(array_map(fn(ScopeEntityInterface $scope) => $scope->getIdentifier(), $scopes), JSON_THROW_ON_ERROR);
    }

    private function hasResourceScopes(?string $scopes): bool
    {
        // Personal access tokens can have scopes for several resources, and belong to each of them.
        $scopes = json_decode($scopes ?? '', true);
        return is_array($scopes) && array_intersect($scopes, array_keys($this->resource->scopes));
    }

    private function requireClient(string $clientId): Client
    {
        $client = $this->getClientEntity($clientId);
        if (!$client instanceof Client) {
            throw OAuthServerException::accessDenied('The client does not belong to this resource or has been revoked.');
        }
        return $client;
    }

    private function refreshToken(string $id): array|false
    {
        return (new Query())
            ->select(['refresh.revoked', 'access.client_id', 'access.scopes'])
            ->from(['refresh' => Table::OAUTH_REFRESH_TOKENS])
            ->innerJoin(['access' => Table::OAUTH_ACCESS_TOKENS], '[[access.id]] = [[refresh.access_token_id]]')
            ->where(['refresh.id' => $id])
            ->one();
    }
}
