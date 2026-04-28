<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\services;

use Craft;
use craft\events\UserGroupEvent;
use CraftCms\Cms\ProjectConfig\Events\ConfigEvent;
use CraftCms\Cms\Support\Facades\UserGroups as UserGroupsFacade;
use CraftCms\Cms\User\Data\UserGroup;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\User\Events\ApplyingUserGroupDelete;
use CraftCms\Cms\User\Events\DeletingUserGroup;
use CraftCms\Cms\User\Events\SavingUserGroup;
use CraftCms\Cms\User\Events\UserGroupDeleted;
use CraftCms\Cms\User\Events\UserGroupSaved;
use Illuminate\Support\Facades\Event;
use yii\base\Component;

/**
 * User Groups service.
 *
 * An instance of the service is available via [[\craft\base\ApplicationTrait::getUserGroups()|`Craft::$app->getUserGroups()`]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 * @deprecated 6.0.0 use {@see \CraftCms\Cms\User\UserGroups} instead.
 */
class UserGroups extends Component
{
    /**
     * @event UserGroupEvent The event that is triggered before a user group is saved.
     */
    public const EVENT_BEFORE_SAVE_USER_GROUP = 'beforeSaveUserGroup';

    /**
     * @event UserGroupEvent The event that is triggered after a user group is saved.
     */
    public const EVENT_AFTER_SAVE_USER_GROUP = 'afterSaveUserGroup';

    /**
     * @event UserGroupEvent The event that is triggered before a user group is deleted.
     */
    public const EVENT_BEFORE_DELETE_USER_GROUP = 'beforeDeleteUserGroup';

    /**
     * @event UserGroupEvent The event that is triggered before a user group delete is applied to the database.
     * @since 3.1.0
     */
    public const EVENT_BEFORE_APPLY_GROUP_DELETE = 'beforeApplyGroupDelete';

    /**
     * @event UserGroupEvent The event that is triggered after a user group is saved.
     */
    public const EVENT_AFTER_DELETE_USER_GROUP = 'afterDeleteUserGroup';

    /**
     * Returns all user groups.
     *
     * @return UserGroup[]
     */
    public function getAllGroups(): array
    {
        return UserGroupsFacade::getAllGroups()->all();
    }

    /**
     * Returns the user groups that the current user is allowed to assign to another user.
     *
     * @param User|null $user The recipient of the user groups. If set, their current groups will be included as well.
     *
     * @return UserGroup[]
     */
    public function getAssignableGroups(?User $user = null): array
    {
        return UserGroupsFacade::getAssignableGroups($user)->all();
    }

    /**
     * Gets a user group by its ID.
     *
     * @param int $groupId
     *
     * @return UserGroup|null
     */
    public function getGroupById(int $groupId): ?UserGroup
    {
        return UserGroupsFacade::getGroupById($groupId);
    }

    /**
     * Gets a user group by its UID.
     *
     * @param string $uid
     *
     * @return UserGroup|null
     */
    public function getGroupByUid(string $uid): ?UserGroup
    {
        return UserGroupsFacade::getGroupByUid($uid);
    }

    /**
     * Gets a user group by its handle.
     *
     * @param string $groupHandle
     *
     * @return UserGroup|null
     */
    public function getGroupByHandle(string $groupHandle): ?UserGroup
    {
        return UserGroupsFacade::getGroupByHandle($groupHandle);
    }

    /**
     * Returns the Craft Team edition’s user group.
     *
     * @return UserGroup
     * @since 5.1.0
     */
    public function getTeamGroup(): UserGroup
    {
        return UserGroupsFacade::getTeamGroup();
    }

    /**
     * Gets user groups by a user ID.
     *
     * @param int $userId
     *
     * @return UserGroup[]
     */
    public function getGroupsByUserId(int $userId): array
    {
        return UserGroupsFacade::getGroupsByUserId($userId)->all();
    }

    /**
     * Eager-loads user groups onto the given users.
     *
     * @param User[] $users The users to eager-load user groups onto
     *
     * @since 3.6.0
     */
    public function eagerLoadGroups(array $users): void
    {
        UserGroupsFacade::eagerLoadGroups($users);
    }

    /**
     * Saves a user group.
     *
     * @param UserGroup $group The user group to be saved
     * @param bool $runValidation Whether the user group should be validated
     *
     * @return bool
     */
    public function saveGroup(UserGroup $group, bool $runValidation = true): bool
    {
        return UserGroupsFacade::saveGroup($group);
    }

    /**
     * Handle any changed user groups.
     *
     * @param ConfigEvent $event
     */
    public function handleChangedUserGroup(ConfigEvent $event): void
    {
        UserGroupsFacade::handleChangedUserGroup($event);
    }

    /**
     * Handle any deleted user groups.
     *
     * @param ConfigEvent $event
     */
    public function handleDeletedUserGroup(ConfigEvent $event): void
    {
        UserGroupsFacade::handleDeletedUserGroup($event);
    }

    /**
     * Deletes a user group by its ID.
     *
     * @param int $groupId The user group's ID
     *
     * @return bool Whether the user group was deleted successfully
     */
    public function deleteGroupById(int $groupId): bool
    {
        return UserGroupsFacade::deleteGroupById($groupId);
    }

    /**
     * Deletes a user group.
     *
     * @param UserGroup $group The user group
     *
     * @return bool Whether the user group was deleted successfully
     * @since 3.0.12
     */
    public function deleteGroup(UserGroup $group): bool
    {
        return UserGroupsFacade::deleteGroup($group);
    }

    public static function registerEvents(): void
    {
        Event::listen(SavingUserGroup::class, function(SavingUserGroup $event) {
            if (Craft::$app->getUserGroups()->hasEventHandlers(self::EVENT_BEFORE_SAVE_USER_GROUP)) {
                Craft::$app->getUserGroups()->trigger(self::EVENT_BEFORE_SAVE_USER_GROUP, $yiiEvent = new UserGroupEvent([
                    'userGroup' => $event->userGroup,
                    'isNew' => $event->isNew,
                ]));
            }
        });

        Event::listen(UserGroupSaved::class, function(UserGroupSaved $event) {
            if (Craft::$app->getUserGroups()->hasEventHandlers(self::EVENT_AFTER_SAVE_USER_GROUP)) {
                Craft::$app->getUserGroups()->trigger(self::EVENT_AFTER_SAVE_USER_GROUP, $yiiEvent = new UserGroupEvent([
                    'userGroup' => $event->userGroup,
                    'isNew' => $event->isNew,
                ]));
            }
        });

        Event::listen(ApplyingUserGroupDelete::class, function(ApplyingUserGroupDelete $event) {
            if (Craft::$app->getUserGroups()->hasEventHandlers(self::EVENT_BEFORE_APPLY_GROUP_DELETE)) {
                Craft::$app->getUserGroups()->trigger(self::EVENT_BEFORE_APPLY_GROUP_DELETE, new UserGroupEvent([
                    'userGroup' => $event->userGroup,
                ]));
            }
        });

        Event::listen(DeletingUserGroup::class, function(DeletingUserGroup $event) {
            if (Craft::$app->getUserGroups()->hasEventHandlers(self::EVENT_BEFORE_DELETE_USER_GROUP)) {
                Craft::$app->getUserGroups()->trigger(self::EVENT_BEFORE_DELETE_USER_GROUP, new UserGroupEvent([
                    'userGroup' => $event->userGroup,
                ]));
            }
        });

        Event::listen(UserGroupDeleted::class, function(UserGroupDeleted $event) {
            if (Craft::$app->getUserGroups()->hasEventHandlers(self::EVENT_AFTER_DELETE_USER_GROUP)) {
                Craft::$app->getUserGroups()->trigger(self::EVENT_AFTER_DELETE_USER_GROUP, new UserGroupEvent([
                    'userGroup' => $event->userGroup,
                ]));
            }
        });
    }
}
