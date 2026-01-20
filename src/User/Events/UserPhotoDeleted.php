<?php

declare(strict_types=1);

namespace CraftCms\Cms\User\Events;

use CraftCms\Cms\User\Elements\User;

/**
 * @event UserPhotoDeleted The event that is triggered after a user photo is deleted.
 */
final class UserPhotoDeleted
{
    public function __construct(
        public User $user,
        public ?int $photoId,
    ) {}
}
