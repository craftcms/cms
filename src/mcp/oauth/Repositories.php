<?php

namespace craft\mcp\oauth;

use Craft;
use craft\db\Query;
use craft\helpers\Db;
use League\OAuth2\Server\Entities\AccessTokenEntityInterface;
use League\OAuth2\Server\Entities\AuthCodeEntityInterface;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Entities\RefreshTokenEntityInterface;
use League\OAuth2\Server\Entities\ScopeEntityInterface;
use League\OAuth2\Server\Exception\UniqueTokenIdentifierConstraintViolationException;
use League\OAuth2\Server\Repositories\AccessTokenRepositoryInterface;
use League\OAuth2\Server\Repositories\AuthCodeRepositoryInterface;
use League\OAuth2\Server\Repositories\ClientRepositoryInterface;
use League\OAuth2\Server\Repositories\RefreshTokenRepositoryInterface;
use League\OAuth2\Server\Repositories\ScopeRepositoryInterface;
use yii\db\IntegrityException;

/** The MCP-only OAuth store. */
class Repositories implements ClientRepositoryInterface, AccessTokenRepositoryInterface, AuthCodeRepositoryInterface, RefreshTokenRepositoryInterface, ScopeRepositoryInterface
{
    public const CLIENTS = '{{%mcp_oauth_clients}}';
    public const TOKENS = '{{%mcp_oauth_access_tokens}}';
    public const CODES = '{{%mcp_oauth_auth_codes}}';
    public const REFRESH = '{{%mcp_oauth_refresh_tokens}}';

    public function getClientEntity(string $clientIdentifier): ?ClientEntityInterface
    {
        $row = (new Query())->from(self::CLIENTS)->where(['id' => $clientIdentifier, 'revoked' => false])->one();
        return $row ? new Client($row) : null;
    }

    public function validateClient(string $clientIdentifier, ?string $clientSecret, ?string $grantType): bool
    {
        return in_array($grantType, ['authorization_code', 'refresh_token'], true) && $this->getClientEntity($clientIdentifier) !== null;
    }

    public function getScopeEntityByIdentifier(string $identifier): ?ScopeEntityInterface
    {
        if ($identifier !== OAuth::SCOPE) {
            return null;
        }

        $scope = new Scope();
        $scope->setIdentifier($identifier);
        return $scope;
    }

    public function finalizeScopes(array $scopes, string $grantType, ClientEntityInterface $clientEntity, ?string $userIdentifier = null, ?string $authCodeId = null): array
    {
        if ($userIdentifier !== null) {
            OAuth::authorizedUser($userIdentifier);
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
        $this->persist(self::TOKENS, [
            'id' => $accessTokenEntity->getIdentifier(),
            'clientId' => $accessTokenEntity->getClient()->getIdentifier(),
            'userId' => $accessTokenEntity->getUserIdentifier(),
            'scopes' => json_encode(array_map(fn(ScopeEntityInterface $scope) => $scope->getIdentifier(), $accessTokenEntity->getScopes()), JSON_THROW_ON_ERROR),
            'expiresAt' => Db::prepareDateForDb($accessTokenEntity->getExpiryDateTime()),
        ]);
    }

    public function revokeAccessToken(string $tokenId): void
    {
        $this->revoke(self::TOKENS, $tokenId);
    }

    public function isAccessTokenRevoked(string $tokenId): bool
    {
        return $this->isRevoked(self::TOKENS, $tokenId);
    }

    public function getNewAuthCode(): AuthCodeEntityInterface
    {
        return new AuthCode();
    }

    public function persistNewAuthCode(AuthCodeEntityInterface $authCodeEntity): void
    {
        $this->persist(self::CODES, [
            'id' => $authCodeEntity->getIdentifier(),
            'clientId' => $authCodeEntity->getClient()->getIdentifier(),
            'userId' => $authCodeEntity->getUserIdentifier(),
            'expiresAt' => Db::prepareDateForDb($authCodeEntity->getExpiryDateTime()),
        ]);
    }

    public function revokeAuthCode(string $codeId): void
    {
        $this->revoke(self::CODES, $codeId);
    }

    public function isAuthCodeRevoked(string $codeId): bool
    {
        return $this->isRevoked(self::CODES, $codeId);
    }

    public function getNewRefreshToken(): ?RefreshTokenEntityInterface
    {
        return new RefreshToken();
    }

    public function persistNewRefreshToken(RefreshTokenEntityInterface $refreshTokenEntity): void
    {
        $this->persist(self::REFRESH, [
            'id' => $refreshTokenEntity->getIdentifier(),
            'accessTokenId' => $refreshTokenEntity->getAccessToken()->getIdentifier(),
            'expiresAt' => Db::prepareDateForDb($refreshTokenEntity->getExpiryDateTime()),
        ]);
    }

    public function revokeRefreshToken(string $tokenId): void
    {
        $this->revoke(self::REFRESH, $tokenId);
    }

    public function isRefreshTokenRevoked(string $tokenId): bool
    {
        return $this->isRevoked(self::REFRESH, $tokenId);
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
        Craft::$app->getDb()->createCommand()->update($table, ['revoked' => true], ['id' => $id])->execute();
    }

    private function isRevoked(string $table, string $id): bool
    {
        $row = (new Query())->from($table)->where(['id' => $id])->one();
        if (!$row || $row['revoked']) {
            return true;
        }

        if (isset($row['clientId']) && !$this->getClientEntity($row['clientId'])) {
            return true;
        }

        return false;
    }
}
