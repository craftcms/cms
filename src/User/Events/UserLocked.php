<?php

declare(strict_types=1);

namespace CraftCms\Cms\User\Events;

use CraftCms\Cms\User\Elements\User;

/**
 * @event UserLocked The event that is triggered after a user is locked.
 */
final class UserLocked extends UserEvent {}
