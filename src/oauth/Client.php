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

    public function __construct(array $row)
    {
        $this->identifier = $row['id'];
        $this->name = $row['name'];
        $this->redirectUri = json_decode($row['redirect_uris'], true, flags: JSON_THROW_ON_ERROR);
        $this->grantTypes = json_decode($row['grant_types'], true, flags: JSON_THROW_ON_ERROR);
        $this->isConfidential = false;
    }

    /** Whether the client may use a grant type, per its `grant_types`, as Passport’s `Client::hasGrantType()` checks. */
    public function supportsGrantType(string $grantType): bool
    {
        return in_array($grantType, $this->grantTypes, true);
    }
}
