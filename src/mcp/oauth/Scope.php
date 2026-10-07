<?php

namespace craft\mcp\oauth;

use League\OAuth2\Server\Entities\ScopeEntityInterface;
use League\OAuth2\Server\Entities\Traits\EntityTrait;

class Scope implements ScopeEntityInterface
{
    use EntityTrait;

    public function jsonSerialize(): string
    {
        return $this->getIdentifier();
    }
}
