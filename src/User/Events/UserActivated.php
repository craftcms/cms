<?php

declare(strict_types=1);

namespace CraftCms\Cms\User\Events;

use CraftCms\Cms\User\Elements\User;

/**
 * @event UserActivated The event that is triggered after a user is activated.
 */
final class UserActivated extends UserEvent {}
