<?php

namespace craft\models;

/**
 * @since 3.0.0
 * @deprecated 6.0.0 use {@see \CraftCms\Cms\User\Data\UserGroup} instead.
 */
class UserGroup extends \CraftCms\Cms\User\Data\UserGroup
{
    public const string EVENT_DEFINE_BEHAVIORS = 'defineBehaviors';
}
