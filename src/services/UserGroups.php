<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\services;

use Craft;
use craft\app\db\Query;
use craft\app\errors\UserGroupNotFoundException;
use craft\app\events\UserGroupEvent;
use craft\app\models\UserGroup;
use craft\app\records\UserGroup as UserGroupRecord;
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
     * @param string|null $indexBy
     *
     * @return array
     */
    public function getAllGroups($indexBy = null)
    {
        $groups = UserGroupRecord::find()
            ->orderBy('name')
            ->indexBy($indexBy)
            ->all();

        foreach ($groups as $key => $value) {
            $groups[$key] = UserGroup::create($value);
        }

        return $groups;
    }

    /**
     * Gets a user group by its ID.
     *
     * @param integer $groupId
     *
     * @return UserGroup
     */
    public function getGroupById($groupId)
    {
        $groupRecord = UserGroupRecord::findOne($groupId);

        if ($groupRecord) {
            return UserGroup::create($groupRecord);
        }

        return null;
    }

    /**
     * Gets a user group by its handle.
     *
     * @param integer $groupHandle
     *
     * @return UserGroup
     */
    public function getGroupByHandle($groupHandle)
    {
        $groupRecord = UserGroupRecord::findOne([
            'handle' => $groupHandle
        ]);

        if ($groupRecord) {
            return UserGroup::create($groupRecord);
        }

        return null;
    }

    /**
     * Gets user groups by a user ID.
     *
     * @param integer     $userId
     * @param string|null $indexBy
     *
     * @return array
     */
    public function getGroupsByUserId($userId, $indexBy = null)
    {
        $groups = (new Query())
            ->select('g.*')
            ->from('{{%usergroups}} g')
            ->innerJoin('{{%usergroups_users}} gu', 'gu.groupId = g.id')
            ->where(['gu.userId' => $userId])
            ->indexBy($indexBy)
            ->all();

        foreach ($groups as $key => $value) {
            $groups[$key] = UserGroup::create($value);
        }

        return $groups;
    }

    /**
     * Saves a user group.
     *
     * @param UserGroup $group         The user group to be saved
     * @param boolean   $runValidation Whether the user group should be validated
     *
     * @return boolean
     */
    public function saveGroup(UserGroup $group, $runValidation = true)
    {
        if ($runValidation && !$group->validate()) {
            Craft::info('User group not saved due to validation error.', __METHOD__);

            return false;
        }

        $isNewGroup = !$group->id;

        // Fire a 'beforeSaveUserGroup' event
        $this->trigger(self::EVENT_BEFORE_SAVE_USER_GROUP, new UserGroupEvent([
            'userGroup' => $group,
            'isNew' => $isNewGroup,
        ]));

        $groupRecord = $this->_getGroupRecordById($group->id);

        $groupRecord->name = $group->name;
        $groupRecord->handle = $group->handle;

        $groupRecord->save(false);

        // Now that we have a group ID, save it on the model
        if ($isNewGroup) {
            $group->id = $groupRecord->id;
        }

        // Fire an 'afterSaveUserGroup' event
        $this->trigger(self::EVENT_AFTER_SAVE_USER_GROUP, new UserGroupEvent([
            'userGroup' => $group,
            'isNew' => $isNewGroup,
        ]));

        return true;
    }

    /**
     * Deletes a user group by its ID.
     *
     * @param integer $groupId
     *
     * @return boolean
     */
    public function deleteGroupById($groupId)
    {
        $group = $this->getGroupById($groupId);

        if (!$group) {
            return false;
        }

        // Fire a 'beforeDeleteUserGroup' event
        $this->trigger(self::EVENT_BEFORE_DELETE_USER_GROUP, new UserGroupEvent([
            'userGroup' => $group,
        ]));

        Craft::$app->getDb()->createCommand()
            ->delete('{{%usergroups}}', ['id' => $groupId])
            ->execute();

        // Fire an 'afterDeleteUserGroup' event
        $this->trigger(self::EVENT_AFTER_DELETE_USER_GROUP, new UserGroupEvent([
            'userGroup' => $group
        ]));

        return true;
    }

    // Private Methods
    // =========================================================================

    /**
     * Gets a group's record.
     *
     * @param integer $groupId
     *
     * @return UserGroupRecord
     */
    private function _getGroupRecordById($groupId = null)
    {
        if ($groupId) {
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
     * @param integer $groupId
     *
     * @return void
     * @throws UserGroupNotFoundException
     */
    private function _noGroupExists($groupId)
    {
        throw new UserGroupNotFoundException("No group exists with the ID '{$groupId}'");
    }
}
