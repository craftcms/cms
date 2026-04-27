<?php

declare(strict_types=1);

namespace CraftCms\Cms\User\Events;

use CraftCms\Cms\User\Elements\User;

/**
 * @event SavingUserPhoto The event that is triggered before a user photo is saved.
 */
class SavingUserPhoto
{
    public function __construct(
        public User $user,
        public ?int $photoId,
    ) {}
}
