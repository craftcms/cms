<?php

declare(strict_types=1);

namespace CraftCms\Cms\Auth\Events;

use CraftCms\Cms\User\Contracts\CraftUser;

/**
 * @event LoginUserRetrieved The event that is triggered after attempting to find a user to sign in
 */
class LoginUserRetrieved
{
    public function __construct(
        public string $loginName,
        public ?CraftUser $user = null,
    ) {}
}
