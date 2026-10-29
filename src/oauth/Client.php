<?php

namespace craft\oauth;

use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Entities\Traits\ClientTrait;
use League\OAuth2\Server\Entities\Traits\EntityTrait;

class Client implements ClientEntityInterface
{
    use ClientTrait;
    use EntityTrait;

    /** @var string[] */
    private array $grantTypes;

    /** @var string[]|null The scopes the client may request, or `null` for any scope, as in Passport. */
    public readonly ?array $scopes;

    public function __construct(array $row)
    {
        $this->identifier = $row['id'];
        $this->name = $row['name'];
        $this->redirectUri = json_decode($row['redirect_uris'], true, flags: JSON_THROW_ON_ERROR);
        $this->grantTypes = json_decode($row['grant_types'], true, flags: JSON_THROW_ON_ERROR);
        $this->scopes = isset($row['scopes']) ? json_decode($row['scopes'], true, flags: JSON_THROW_ON_ERROR) : null;
        $this->isConfidential = false;
    }

    /** Whether the client may request a scope, per its `scopes`, as Passport’s `Client::hasScope()` checks. */
    public function hasScope(string $scope): bool
    {
        return $this->scopes === null || in_array($scope, $this->scopes, true);
    }

    /** Whether the client may use a grant type, per its `grant_types`, as Passport’s `Client::hasGrantType()` checks. */
    public function supportsGrantType(string $grantType): bool
    {
        return in_array($grantType, $this->grantTypes, true);
    }
}
