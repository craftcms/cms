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
     *
     * You may set [[UserGroupEvent::isValid]] to `false` to prevent the user group from being saved.
     */
    const EVENT_BEFORE_SAVE_USER_GROUP = 'beforeSaveUserGroup';

    /**
     * @event UserGroupEvent The event that is triggered after a user group is saved.
     */
    const EVENT_AFTER_SAVE_USER_GROUP = 'afterSaveUserGroup';

    /**
     * @event UserGroupEvent The event that is triggered before a user group is deleted.
     *
     * You may set [[UserGroupEvent::isValid]] to `false` to prevent the user group from being deleted.
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
     * @param UserGroup $group
     *
     * @return boolean
     */
    public function saveGroup(UserGroup $group)
    {
        $success = false;

        // Fire a 'beforeSaveUserGroup' event
        $event = new UserGroupEvent([
            'userGroup' => $group,
        ]);

        $this->trigger(self::EVENT_BEFORE_SAVE_USER_GROUP, $event);

        if ($event->isValid) {
            $groupRecord = $this->_getGroupRecordById($group->id);

            $groupRecord->name = $group->name;
            $groupRecord->handle = $group->handle;

            if ($groupRecord->save()) {
                // Now that we have a group ID, save it on the model
                if (!$group->id) {
                    $group->id = $groupRecord->id;
                }

                $success = true;
            } else {
                $group->addErrors($groupRecord->getErrors());
            }
        }

        if ($success) {
            // Fire an 'afterSaveUserGroup' event
            $this->trigger(self::EVENT_AFTER_SAVE_USER_GROUP, new UserGroupEvent([
                'userGroup' => $group
            ]));
        }

        return $success;
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

        // Fire a 'beforeDeleteUserGroup' event
        $event = new UserGroupEvent([
            'userGroup' => $group,
        ]);

        $this->trigger(self::EVENT_BEFORE_DELETE_USER_GROUP, $event);

        if ($event->isValid) {
            Craft::$app->getDb()->createCommand()
                ->delete('{{%usergroups}}', ['id' => $groupId])
                ->execute();

            // Fire an 'afterDeleteUserGroup' event
            $this->trigger(self::EVENT_AFTER_DELETE_USER_GROUP,
                new UserGroupEvent([
                    'userGroup' => $group
                ]));

            return true;
        }

        return false;
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
