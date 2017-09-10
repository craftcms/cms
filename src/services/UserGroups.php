<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\services;

use Craft;
use craft\db\Query;
use craft\errors\UserGroupNotFoundException;
use craft\events\UserGroupEvent;
use craft\models\UserGroup;
use craft\records\UserGroup as UserGroupRecord;
use yii\base\Component;

Craft::$app->requireEdition(Craft::Pro);

/**
 * Class UserGroups service.
 *
 * An instance of the UserGroups service is globally accessible in Craft via [[Application::userGroups `Craft::$app->getUserGroups()`]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
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
     * @event UserGroupEvent The event that is triggered after a user group is saved.
     */
    const EVENT_AFTER_DELETE_USER_GROUP = 'afterDeleteUserGroup';

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
            ]));
        }

        return $groups;
    }

    /**
     * Gets a user group by its ID.
     *
     * @param int $groupId
     *
     * @return UserGroup
     */
    public function getGroupById(int $groupId): UserGroup
    {
        $result = $this->_createUserGroupsQuery()
            ->where(['id' => $groupId])
            ->one();

        if ($result) {
            return new UserGroup($result);
        }

        return null;
    }

    /**
     * Gets a user group by its handle.
     *
     * @param int $groupHandle
     *
     * @return UserGroup
     */
    public function getGroupByHandle(int $groupHandle): UserGroup
    {
        $result = $this->_createUserGroupsQuery()
            ->where(['handle' => $groupHandle])
            ->one();

        if ($result) {
            return new UserGroup($result);
        }

        return null;
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
     * @param UserGroup $group         The user group to be saved
     * @param bool      $runValidation Whether the user group should be validated
     *
     * @return bool
     */
    public function saveGroup(UserGroup $group, bool $runValidation = true): bool
    {
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

        $groupRecord = $this->_getGroupRecordById($group->id);

        $groupRecord->name = $group->name;
        $groupRecord->handle = $group->handle;

        $groupRecord->save(false);

        // Now that we have a group ID, save it on the model
        if ($isNewGroup) {
            $group->id = $groupRecord->id;
        }

        // Fire an 'afterSaveUserGroup' event
        if ($this->hasEventHandlers(self::EVENT_AFTER_SAVE_USER_GROUP)) {
            $this->trigger(self::EVENT_AFTER_SAVE_USER_GROUP, new UserGroupEvent([
                'userGroup' => $group,
                'isNew' => $isNewGroup,
            ]));
        }

        return true;
    }

    /**
     * Deletes a user group by its ID.
     *
     * @param int $groupId
     *
     * @return bool
     */
    public function deleteGroupById(int $groupId): bool
    {
        $group = $this->getGroupById($groupId);

        if (!$group) {
            return false;
        }

        // Fire a 'beforeDeleteUserGroup' event
        if ($this->hasEventHandlers(self::EVENT_BEFORE_DELETE_USER_GROUP)) {
            $this->trigger(self::EVENT_BEFORE_DELETE_USER_GROUP, new UserGroupEvent([
                'userGroup' => $group,
            ]));
        }

        Craft::$app->getDb()->createCommand()
            ->delete('{{%usergroups}}', ['id' => $groupId])
            ->execute();

        // Fire an 'afterDeleteUserGroup' event
        if ($this->hasEventHandlers(self::EVENT_AFTER_DELETE_USER_GROUP)) {
            $this->trigger(self::EVENT_AFTER_DELETE_USER_GROUP, new UserGroupEvent([
                'userGroup' => $group
            ]));
        }

        return true;
    }

    // Private Methods
    // =========================================================================

    /**
     * Gets a group's record.
     *
     * @param int|null $groupId
     *
     * @return UserGroupRecord
     */
    private function _getGroupRecordById(int $groupId = null): UserGroupRecord
    {
        if ($groupId !== null) {
            $groupRecord = UserGroupRecord::findOne($groupId);

            if (!$groupRecord) {
                $this->_noGroupExists($groupId);
            }
        } else {
            $groupRecord = new UserGroupRecord();
        }

        return $groupRecord;
    }

    /**
     * Throws a "No group exists" exception.
     *
     * @param int $groupId
     *
     * @return void
     * @throws UserGroupNotFoundException
     */
    private function _noGroupExists(int $groupId)
    {
        throw new UserGroupNotFoundException("No group exists with the ID '{$groupId}'");
    }

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
            ])
            ->from(['{{%usergroups}}']);
    }
}
