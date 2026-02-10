<?php

declare(strict_types=1);

namespace CraftCms\Cms\User\Events;

use CraftCms\Cms\User\Elements\User;

/**
 * @event DefineName The event that is triggered when defining the user’s name, as returned by {@see User::getName()} or {@see User::__toString()}.
 */
final class DefineName
{
    public function __construct(
        public User $user,
        public ?string $name = null,
    ) {}
}
