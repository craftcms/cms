<?php

declare(strict_types=1);

namespace CraftCms\Cms\User\Events;

use CraftCms\Cms\User\Elements\User;

/**
 * @event DefineEditUserScreens The event that is triggered when defining the screens that should be
 * shown for the user being edited.
 */
final class DefineEditUserScreens
{
    public function __construct(
        public User $currentUser,
        public User $editedUser,

        /**
         * @var array<string,array> The screens that should be shown for the user being edited.
         *
         * Each screen should be represented by a sub-array whose key is the screen ID, and which has the following keys:
         *
         * - `label` – The screen’s nav item label.
         * - `url` – The screen’s URL (optional; a URL like `myaccount/screen-key`
         *   or `users/x/screen-key` will be used by default).
         */
        public array $screens,
    ) {}
}
