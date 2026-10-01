<?php

declare(strict_types=1);

namespace CraftCms\Cms\User\Events;

use CraftCms\Cms\User\Elements\User;

/**
 * @event GroupsAndPermissionsAssigned The event that is triggered after user groups and permissions have been assigned to the user getting saved
 *
 * @since 6.0.0
 */
class GroupsAndPermissionsAssigned extends UserEvent {}
