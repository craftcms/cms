<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\services;

use Craft;
use craft\db\Query;
use craft\db\Table;
use craft\elements\User;
use craft\errors\WrongEditionException;
use craft\events\ConfigEvent;
use craft\events\UserGroupEvent;
use craft\helpers\Db;
use craft\helpers\StringHelper;
use craft\models\UserGroup;
use craft\records\UserGroup as UserGroupRecord;
use yii\base\Component;

/**
 * User Groups service.
 * An instance of the User Groups service is globally accessible in Craft via [[\craft\base\ApplicationTrait::getUserGroups()|`Craft::$app->userGroups`]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class UserGroups extends Component
{
    // Constants
    // =========================================================================

    /**
     * @event UserGroupEvent The event that is triggered before a user group is saved.
     */
    const EVENT_BEFORE_SAVE_USER_GROUP = 'beforeSaveUserGroup';

    /**
     * @event UserGroupEvent The event that is triggered after a user group is saved.
     */
    const EVENT_AFTER_SAVE_USER_GROUP = 'afterSaveUserGroup';

    /**
     * @event UserGroupEvent The event that is triggered before a user group is deleted.
     */
    const EVENT_BEFORE_DELETE_USER_GROUP = 'beforeDeleteUserGroup';

    /**
     * @event UserGroupEvent The event that is triggered before a user group delete is applied to the database.
     */
    const EVENT_BEFORE_APPLY_GROUP_DELETE = 'beforeApplyGroupDelete';

    /**
     * @event UserGroupEvent The event that is triggered after a user group is saved.
     */
    const EVENT_AFTER_DELETE_USER_GROUP = 'afterDeleteUserGroup';

    const CONFIG_USERPGROUPS_KEY = 'users.groups';

    // Public Methods
    // =========================================================================

    /**
     * Returns all user groups.
     *
     * @return UserGroup[]
     */
    public function getAllGroups(): array
    {
        $groups = UserGroupRecord::find()
            ->orderBy(['name' => SORT_ASC])
            ->all();

        foreach ($groups as $key => $value) {
            $groups[$key] = new UserGroup($value->toArray([
                'id',
                'name',
                'handle',
                'uid'
            ]));
        }

        return $groups;
    }

    /**
     * Returns the user groups that the current user is allowed to assign to another user.
     *
     * @param User|null $user The recipient of the user groups. If set, their current groups will be included as well.
     * @return UserGroup[]
     */
    public function getAssignableGroups(User $user = null): array
    {
        $currentUser = Craft::$app->getUser()->getIdentity();
        if (!$currentUser && !$user) {
            return [];
        }

        // If either user is an admin, all groups are fair game
        if (($currentUser !== null && $currentUser->admin) || ($user !== null && $user->admin)) {
            return $this->getAllGroups();
        }

        $assignableGroups = [];

        foreach ($this->getAllGroups() as $group) {
            if (
                ($currentUser !== null && (
                        $currentUser->isInGroup($group) ||
                        $currentUser->can('assignUserGroup:' . $group->uid)
                    )) ||
                ($user !== null && $user->isInGroup($group))
            ) {
                $assignableGroups[] = $group;
            }
        }

        return $assignableGroups;
    }

    /**
     * Gets a user group by its ID.
     *
     * @param int $groupId
     * @return UserGroup|null
     */
    public function getGroupById(int $groupId)
    {
        $result = $this->_createUserGroupsQuery()
            ->where(['id' => $groupId])
            ->one();

        return $result ? new UserGroup($result) : null;
    }

    /**
     * Gets a user group by its UID.
     *
     * @param string $uid
     * @return UserGroup|null
     */
    public function getGroupByUid(string $uid)
    {
        $result = $this->_createUserGroupsQuery()
            ->where(['uid' => $uid])
            ->one();

        return $result ? new UserGroup($result) : null;
    }

    /**
     * Gets a user group by its handle.
     *
     * @param string $groupHandle
     * @return UserGroup|null
     */
    public function getGroupByHandle(string $groupHandle)
    {
        $result = $this->_createUserGroupsQuery()
            ->where(['handle' => $groupHandle])
            ->one();

        return $result ? new UserGroup($result) : null;
    }

    /**
     * Gets user groups by a user ID.
     *
     * @param int $userId
     * @return UserGroup[]
     */
    public function getGroupsByUserId(int $userId): array
    {
        $groups = (new Query())
            ->select([
                'g.id',
                'g.name',
                'g.handle',
            ])
            ->from(['{{%usergroups}} g'])
            ->innerJoin('{{%usergroups_users}} gu', '[[gu.groupId]] = [[g.id]]')
            ->where(['gu.userId' => $userId])
            ->all();

        foreach ($groups as $key => $value) {
            $groups[$key] = new UserGroup($value);
        }

        return $groups;
    }

    /**
     * Saves a user group.
     *
     * @param UserGroup $group The user group to be saved
     * @param bool $runValidation Whether the user group should be validated
     * @return bool
     * @throws WrongEditionException if this is called from Craft Solo edition
     */
    public function saveGroup(UserGroup $group, bool $runValidation = true): bool
    {
        Craft::$app->requireEdition(Craft::Pro);

        $isNewGroup = !$group->id;

        // Fire a 'beforeSaveUserGroup' event
        if ($this->hasEventHandlers(self::EVENT_BEFORE_SAVE_USER_GROUP)) {
            $this->trigger(self::EVENT_BEFORE_SAVE_USER_GROUP, new UserGroupEvent([
                'userGroup' => $group,
                'isNew' => $isNewGroup,
            ]));
        }

        if ($runValidation && !$group->validate()) {
            Craft::info('User group not saved due to validation error.', __METHOD__);
            return false;
        }

        $projectConfig = Craft::$app->getProjectConfig();

        if ($isNewGroup) {
            $group->uid = StringHelper::UUID();
        } else if (!$group->uid) {
            $group->uid = Db::uidById(Table::USERGROUPS, $group->id);
        }

        $configPath = self::CONFIG_USERPGROUPS_KEY . '.' . $group->uid;

        // Save everything except permissions. Not ours to touch.
        $configData = [
            'name' => $group->name,
            'handle' => $group->handle
        ];

        $projectConfig->set($configPath, $configData);

        // Now that we have a group ID, save it on the model
        if ($isNewGroup) {
            $group->id = Db::idByUid(Table::USERGROUPS, $group->uid);
        }

        return true;
    }

    /**
     * Handle any changed user groups.
     *
     * @param ConfigEvent $event
     */
    public function handleChangedUserGroup(ConfigEvent $event)
    {
        $uid = $event->tokenMatches[0];
        $data = $event->newValue;

        $groupRecord = UserGroupRecord::findOne(['uid' => $uid]) ?? new UserGroupRecord();
        $isNewGroup = $groupRecord->getIsNewRecord();

        $groupRecord->name = $data['name'];
        $groupRecord->handle = $data['handle'];
        $groupRecord->uid = $uid;

        $groupRecord->save(false);

        // Prevent permission information from being saved. Allowing it would prevent the appropriate event from firing.
        $event->newValue['permissions'] = $event->oldValue['permissions'] ?? [];

        // Fire an 'afterSaveUserGroup' event
        if ($this->hasEventHandlers(self::EVENT_AFTER_SAVE_USER_GROUP)) {
            $this->trigger(self::EVENT_AFTER_SAVE_USER_GROUP, new UserGroupEvent([
                'userGroup' => $this->getGroupById($groupRecord->id),
                'isNew' => $isNewGroup,
            ]));
        }
    }

    /**
     * Handle any deleted user groups.
     *
     * @param ConfigEvent $event
     */
    public function handleDeletedUserGroup(ConfigEvent $event)
    {
        $uid = $event->tokenMatches[0];

        $group = $this->getGroupByUid($uid);

        // Fire a 'beforeApplyGroupDelete' event
        if ($this->hasEventHandlers(self::EVENT_BEFORE_APPLY_GROUP_DELETE)) {
            $this->trigger(self::EVENT_BEFORE_APPLY_GROUP_DELETE, new UserGroupEvent([
                'userGroup' => $group,
            ]));
        }

        Craft::$app->getDb()->createCommand()
            ->delete(Table::USERGROUPS, ['uid' => $uid])
            ->execute();

        // Fire an 'afterDeleteUserGroup' event
        if ($this->hasEventHandlers(self::EVENT_AFTER_DELETE_USER_GROUP)) {
            $this->trigger(self::EVENT_AFTER_DELETE_USER_GROUP, new UserGroupEvent([
                'userGroup' => $group,
            ]));
        }
    }

    /**
     * Deletes a user group by its ID.
     *
     * @param int $groupId The user group's ID
     * @return bool Whether the user group was deleted successfully
     * @throws WrongEditionException if this is called from Craft Solo edition
     */
    public function deleteGroupById(int $groupId): bool
    {
        Craft::$app->requireEdition(Craft::Pro);

        $group = $this->getGroupById($groupId);

        if (!$group) {
            return false;
        }

        return $this->deleteGroup($group);
    }

    /**
     * Deletes a user group.
     *
     * @param UserGroup $group The user group
     * @return bool Whether the user group was deleted successfully
     * @throws WrongEditionException if this is called from Craft Solo edition
     */
    public function deleteGroup(UserGroup $group): bool
    {
        Craft::$app->requireEdition(Craft::Pro);

        if (!$group) {
            return false;
        }

        // Fire a 'beforeDeleteUserGroup' event
        if ($this->hasEventHandlers(self::EVENT_BEFORE_DELETE_USER_GROUP)) {
            $this->trigger(self::EVENT_BEFORE_DELETE_USER_GROUP, new UserGroupEvent([
                'userGroup' => $group,
            ]));
        }

        Craft::$app->getProjectConfig()->remove(self::CONFIG_USERPGROUPS_KEY . '.' . $group->uid);
        return true;
    }

    // Private Methods
    // =========================================================================

    /**
     * @return Query
     */
    private function _createUserGroupsQuery(): Query
    {
        return (new Query())
            ->select([
                'id',
                'name',
                'handle',
                'uid'
            ])
            ->from([Table::USERGROUPS]);
    }
}
