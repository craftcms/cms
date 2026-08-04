<?php

declare(strict_types=1);

namespace CraftCms\Cms\User\Events;

use CraftCms\Cms\User\Elements\User;

/**
 * @event UserPhotoDeleting The event that is triggered before a user photo is deleted.
 */
class UserPhotoDeleting
{
    public function __construct(
        public User $user,
        public ?int $photoId,
    ) {}
}
