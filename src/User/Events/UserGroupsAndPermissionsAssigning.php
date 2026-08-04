<?php

declare(strict_types=1);

namespace CraftCms\Cms\User\Events;

use CraftCms\Cms\User\Elements\User;

/**
 * @event UserGroupsAndPermissionsAssigning The event that is triggered BEFORE user groups and permissions ARE assigned to the user getting saved
 */
class UserGroupsAndPermissionsAssigning extends UserEvent {}
