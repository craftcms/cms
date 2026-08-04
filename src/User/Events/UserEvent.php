<?php

declare(strict_types=1);

namespace CraftCms\Cms\User\Events;

use CraftCms\Cms\User\Elements\User;

abstract class UserEvent
{
    public function __construct(
        public User $user,
    ) {}
}
