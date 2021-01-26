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
use craft\helpers\ArrayHelper;
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
 * @since 3.0.0
 */
class UserGroups extends Component
{
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
     * @since 3.1.0
     */
    const EVENT_BEFORE_APPLY_GROUP_DELETE = 'beforeApplyGroupDelete';

    /**
     * @event UserGroupEvent The event that is triggered after a user group is saved.
     */
    const EVENT_AFTER_DELETE_USER_GROUP = 'afterDeleteUserGroup';

    const CONFIG_USERPGROUPS_KEY = 'users.groups';

    /**
     * Returns all user groups.
     *
     * @return UserGroup[]
     */
    public function getAllGroups(): array
    {
        $results = $this->_createUserGroupsQuery()
            ->orderBy(['name' => SORT_ASC])
            ->all();

        foreach ($results as $key => $result) {
            $results[$key] = new UserGroup($result);
        }

        return $results;
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
                'g.uid'
            ])
            ->from(['g' => Table::USERGROUPS])
            ->innerJoin(['gu' => Table::USERGROUPS_USERS], '[[gu.groupId]] = [[g.id]]')
            ->where(['gu.userId' => $userId])
            ->all();

        foreach ($groups as $key => $value) {
            $groups[$key] = new UserGroup($value);
        }

        return $groups;
    }

    /**
     * Eager-loads user groups onto the given users.
     *
     * @param User[] $users The users to eager-load user groups onto
     * @since 3.6.0
     */
    public function eagerLoadGroups(array $users): void
    {
        if (empty($users)) {
            return;
        }

        $assignments = (new Query())
            ->select(['groupId', 'userId'])
            ->from([Table::USERGROUPS_USERS])
            ->where([
                'userId' => array_unique(ArrayHelper::getColumn($users, 'id')),
            ])
            ->all();

        $groupsByUserId = [];

        if (!empty($assignments)) {
            // Get the user groups, indexed by their IDs
            $groups = [];
            $groupResults = $this->_createUserGroupsQuery()
                ->where([
                    'id' => array_unique(ArrayHelper::getColumn($assignments, 'groupId')),
                ])
                ->all();
            foreach ($groupResults as $result) {
                $groups[$result['id']] = new UserGroup($result);
            }

            // Create batches of user groups by user ID
            foreach ($assignments as $assignment) {
                if (isset($groups[$assignment['groupId']])) {
                    $groupsByUserId[$assignment['userId']][] = $groups[$assignment['groupId']];
                }
            }
        }

        // Assign the user groups
        foreach ($users as $user) {
            $user->setGroups($groupsByUserId[$user->id] ?? []);
        }
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
        $configData = $group->getConfig(false);
        $projectConfig->set($configPath, $configData, "Save user group “{$group->handle}”");

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

        // todo: remove schema version conditions after next beakpoint
        if (version_compare(Craft::$app->getInstalledSchemaVersion(), '3.5.5', '>=')) {
            $groupRecord->description = $data['description'] ?? null;
        }

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

        // Invalidate user caches
        Craft::$app->getElements()->invalidateCachesForElementType(User::class);
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

        Db::delete(Table::USERGROUPS, [
            'uid' => $uid,
        ]);

        // Fire an 'afterDeleteUserGroup' event
        if ($this->hasEventHandlers(self::EVENT_AFTER_DELETE_USER_GROUP)) {
            $this->trigger(self::EVENT_AFTER_DELETE_USER_GROUP, new UserGroupEvent([
                'userGroup' => $group,
            ]));
        }

        // Invalidate user caches
        Craft::$app->getElements()->invalidateCachesForElementType(User::class);
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
     * @since 3.0.12
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

        Craft::$app->getProjectConfig()->remove(self::CONFIG_USERPGROUPS_KEY . '.' . $group->uid, "Delete the “{$group->handle}” user group");
        return true;
    }

    /**
     * @return Query
     */
    private function _createUserGroupsQuery(): Query
    {
        $query = (new Query())
            ->select([
                'id',
                'name',
                'handle',
                'uid'
            ])
            ->from([Table::USERGROUPS]);

        // todo: remove schema version conditions after next beakpoint
        $schemaVersion = Craft::$app->getInstalledSchemaVersion();
        if (version_compare($schemaVersion, '3.5.5', '>=')) {
            $query->addSelect(['description']);
        }

        return $query;
    }
}
