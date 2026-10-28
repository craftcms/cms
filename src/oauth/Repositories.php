<?php

namespace craft\oauth;

use Craft;
use craft\db\Query;
use craft\db\Table;
use craft\helpers\Db;
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

/** Resource-bound OAuth client and credential storage. */
class Repositories implements ClientRepositoryInterface, AccessTokenRepositoryInterface, AuthCodeRepositoryInterface, RefreshTokenRepositoryInterface, ScopeRepositoryInterface
{
    public function __construct(private Resource $resource)
    {
    }

    public function getClientEntity(string $clientIdentifier): ?ClientEntityInterface
    {
        $row = (new Query())->from(Table::OAUTH_CLIENTS)->where(['id' => $clientIdentifier, 'revoked' => false, 'resourceId' => $this->resource->id])->one();
        return $row ? new Client($row) : null;
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

        foreach ($scopes as $scope) {
            if (!isset($this->resource->scopes[$scope->getIdentifier()])) {
                throw OAuthServerException::invalidScope($scope->getIdentifier());
            }
        }

        $this->requireClient($clientEntity->getIdentifier());
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

        $this->persist(Table::OAUTH_ACCESS_TOKENS, [
            'resourceId' => $this->resource->id,
            'id' => $accessTokenEntity->getIdentifier(),
            'clientId' => $accessTokenEntity->getClient()->getIdentifier(),
            'userId' => $accessTokenEntity->getUserIdentifier(),
            'scopes' => json_encode(array_map(fn(ScopeEntityInterface $scope) => $scope->getIdentifier(), $accessTokenEntity->getScopes()), JSON_THROW_ON_ERROR),
            'expiresAt' => Db::prepareDateForDb($accessTokenEntity->getExpiryDateTime()),
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
            'resourceId' => $this->resource->id,
            'id' => $authCodeEntity->getIdentifier(),
            'clientId' => $authCodeEntity->getClient()->getIdentifier(),
            'userId' => $authCodeEntity->getUserIdentifier(),
            'expiresAt' => Db::prepareDateForDb($authCodeEntity->getExpiryDateTime()),
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
        $accessToken = (new Query())->from(Table::OAUTH_ACCESS_TOKENS)->where(['id' => $accessTokenId, 'resourceId' => $this->resource->id])->one();
        if (!$accessToken || !$this->hasResourceScopes($accessToken['scopes'])) {
            throw OAuthServerException::accessDenied('The access token does not belong to this resource.');
        }

        $this->requireClient($accessToken['clientId']);

        $this->persist(Table::OAUTH_REFRESH_TOKENS, [
            'id' => $refreshTokenEntity->getIdentifier(),
            'accessTokenId' => $accessTokenId,
            'expiresAt' => Db::prepareDateForDb($refreshTokenEntity->getExpiryDateTime()),
        ]);
    }

    public function revokeRefreshToken(string $tokenId): void
    {
        $accessTokens = (new Query())->select('id')->from(Table::OAUTH_ACCESS_TOKENS)->where(['resourceId' => $this->resource->id]);
        Craft::$app->getDb()->createCommand()->update(Table::OAUTH_REFRESH_TOKENS, ['revoked' => true], ['id' => $tokenId, 'accessTokenId' => $accessTokens])->execute();
    }

    public function isRefreshTokenRevoked(string $tokenId): bool
    {
        $row = $this->refreshToken($tokenId);
        return !$row || $row['revoked'] || !$this->hasResourceScopes($row['scopes']) || !$this->getClientEntity($row['clientId']);
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
        Craft::$app->getDb()->createCommand()->update($table, ['revoked' => true], ['id' => $id, 'resourceId' => $this->resource->id])->execute();
    }

    private function isRevoked(string $table, string $id): bool
    {
        $row = (new Query())->from($table)->where(['id' => $id, 'resourceId' => $this->resource->id])->one();
        if (!$row || $row['revoked']) {
            return true;
        }

        if ($table === Table::OAUTH_ACCESS_TOKENS && !$this->hasResourceScopes($row['scopes'])) {
            return true;
        }

        if (isset($row['clientId']) && !$this->getClientEntity($row['clientId'])) {
            return true;
        }

        return false;
    }

    private function hasResourceScopes(string $scopes): bool
    {
        $scopes = json_decode($scopes, true);
        return is_array($scopes) && $this->resource->allowsScopes($scopes);
    }

    private function requireClient(string $clientId): void
    {
        if (!$this->getClientEntity($clientId)) {
            throw OAuthServerException::accessDenied('The client does not belong to this resource or has been revoked.');
        }
    }

    private function refreshToken(string $id): array|false
    {
        return (new Query())
            ->select(['refresh.revoked', 'access.clientId', 'access.scopes'])
            ->from(['refresh' => Table::OAUTH_REFRESH_TOKENS])
            ->innerJoin(['access' => Table::OAUTH_ACCESS_TOKENS], '[[access.id]] = [[refresh.accessTokenId]]')
            ->where(['refresh.id' => $id, 'access.resourceId' => $this->resource->id])
            ->one();
    }
}
