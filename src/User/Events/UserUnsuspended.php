<?php

declare(strict_types=1);

namespace CraftCms\Cms\User\Events;

use CraftCms\Cms\User\Elements\User;

/**
 * @event UserUnsuspended The event that is triggered after a user is unsuspended.
 *
 * @since 6.0.0
 */
class UserUnsuspended extends UserEvent {}
