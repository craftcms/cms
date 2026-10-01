<?php

declare(strict_types=1);

namespace CraftCms\Cms\User\Events;

use CraftCms\Cms\User\Elements\User;

/**
 * @event UserDeactivated The event that is triggered after a user is deactivated.
 *
 * @since 6.0.0
 */
class UserDeactivated extends UserEvent {}
