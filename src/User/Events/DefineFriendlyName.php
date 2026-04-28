<?php

declare(strict_types=1);

namespace CraftCms\Cms\User\Events;

use CraftCms\Cms\User\Elements\User;

/**
 * @event DefineFriendlyName The event that is triggered when defining the user’s friendly name, as returned by {@see User::getFriendlyName()}.
 */
class DefineFriendlyName
{
    public function __construct(
        public User $user,
        public ?string $name = null,
    ) {}
}
