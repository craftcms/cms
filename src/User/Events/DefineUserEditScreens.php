<?php

declare(strict_types=1);

namespace CraftCms\Cms\User\Events;

use CraftCms\Cms\Element\Elements\User;

/**
 * @event DefineUserEditScreens The event that is triggered when defining the screens that should be
 * shown for the user being edited.
 */
final class DefineUserEditScreens
{
    public function __construct(
        public User $currentUser,
        public User $editedUser,
        public array $screens,
    ) {}
}
