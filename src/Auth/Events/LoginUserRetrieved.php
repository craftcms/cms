<?php

declare(strict_types=1);

namespace CraftCms\Cms\Auth\Events;

use CraftCms\Cms\User\Elements\User;

final class LoginUserRetrieved
{
    public function __construct(
        public string $loginName,
        public User $user,
    ) {}
}
