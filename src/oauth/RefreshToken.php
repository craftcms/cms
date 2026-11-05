<?php

namespace craft\oauth;

use League\OAuth2\Server\Entities\RefreshTokenEntityInterface;
use League\OAuth2\Server\Entities\Traits\EntityTrait;
use League\OAuth2\Server\Entities\Traits\RefreshTokenTrait;

class RefreshToken implements RefreshTokenEntityInterface
{
    use RefreshTokenTrait;
    use EntityTrait;
}
