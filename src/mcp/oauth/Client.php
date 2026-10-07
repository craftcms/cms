<?php

namespace craft\mcp\oauth;

use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Entities\Traits\ClientTrait;
use League\OAuth2\Server\Entities\Traits\EntityTrait;

class Client implements ClientEntityInterface
{
    use ClientTrait;
    use EntityTrait;

    public function __construct(array $row)
    {
        $this->identifier = $row['id'];
        $this->name = $row['name'];
        $this->redirectUri = json_decode($row['redirectUris'], true, flags: JSON_THROW_ON_ERROR);
        $this->isConfidential = false;
    }
}
