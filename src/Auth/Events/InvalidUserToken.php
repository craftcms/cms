<?php

declare(strict_types=1);

namespace CraftCms\Cms\Auth\Events;

use CraftCms\Cms\User\Elements\User;

final readonly class InvalidUserToken
{
    public function __construct(
        public ?User $user,
    ) {}
}
