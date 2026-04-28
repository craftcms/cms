<?php

declare(strict_types=1);

namespace CraftCms\Cms\User\Events;

use CraftCms\Cms\User\Elements\User;

/**
 * @event DeletingUserPhoto The event that is triggered before a user photo is deleted.
 */
class DeletingUserPhoto
{
    public function __construct(
        public User $user,
        public ?int $photoId,
    ) {}
}
