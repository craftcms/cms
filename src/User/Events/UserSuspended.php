<?php

declare(strict_types=1);

namespace CraftCms\Cms\User\Events;

use CraftCms\Cms\User\Elements\User;

/**
 * @event UserSuspended The event that is triggered after a user is suspended.
 */
class UserSuspended extends UserEvent {}
