<?php

declare(strict_types=1);

namespace CraftCms\Cms\Auth\Events;

use CraftCms\Cms\User\Elements\User;

/**
 * @event LoginUserRetrieved The event that is triggered after attempting to find a user to sign in
 */
final class LoginUserRetrieved
{
    public function __construct(
        public string $loginName,
        public User $user,
    ) {}
}
