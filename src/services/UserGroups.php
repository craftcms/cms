<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\services;

use yii\base\Component;
use craft\app\Craft;
use craft\app\models\UserGroup          as UserGroupModel;
use craft\app\records\UserGroup         as UserGroupRecord;
use craft\app\web\Application;

craft()->requireEdition(Craft::Pro);

/**
 * Class UserGroups service.
 *
 * An instance of the UserGroups service is globally accessible in Craft via [[Application::userGroups `craft()->userGroups`]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class UserGroups extends Component
{
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
		$groupRecords = UserGroupRecord::model()->ordered()->findAll();
		return UserGroupModel::populateModels($groupRecords, $indexBy);
	}

	/**
	 * Gets a user group by its ID.
	 *
	 * @param int $groupId
	 *
	 * @return UserGroupModel
	 */
	public function getGroupById($groupId)
	{
		$groupRecord = UserGroupRecord::model()->findById($groupId);

		if ($groupRecord)
		{
			return UserGroupModel::populateModel($groupRecord);
		}
	}

	/**
	 * Gets a user group by its handle.
	 *
	 * @param int $groupHandle
	 *
	 * @return UserGroupModel
	 */
	public function getGroupByHandle($groupHandle)
	{
		$groupRecord = UserGroupRecord::model()->findByAttributes(array(
			'handle' => $groupHandle
		));

		if ($groupRecord)
		{
			return UserGroupModel::populateModel($groupRecord);
		}
	}

	/**
	 * Gets user groups by a user ID.
	 *
	 * @param int         $userId
	 * @param string|null $indexBy
	 *
	 * @return array
	 */
	public function getGroupsByUserId($userId, $indexBy = null)
	{
		$query = craft()->db->createCommand()
			->select('g.*')
			->from('usergroups g')
			->join('usergroups_users gu', 'gu.groupId = g.id')
			->where(array('gu.userId' => $userId))
			->queryAll();

		return UserGroupModel::populateModels($query, $indexBy);
	}

	/**
	 * Saves a user group.
	 *
	 * @param UserGroupModel $group
	 *
	 * @return bool
	 */
	public function saveGroup(UserGroupModel $group)
	{
		$groupRecord = $this->_getGroupRecordById($group->id);

		$groupRecord->name = $group->name;
		$groupRecord->handle = $group->handle;

		if ($groupRecord->save())
		{
			// Now that we have a group ID, save it on the model
			if (!$group->id)
			{
				$group->id = $groupRecord->id;
			}

			return true;
		}
		else
		{
			$group->addErrors($groupRecord->getErrors());
			return false;
		}
	}

	/**
	 * Assigns a user to a given list of user groups.
	 *
	 * @param int       $userId   The user’s ID.
	 * @param int|array $groupIds The groups’ IDs.
	 *
	 * @return bool Whether the users were successfully assigned to the groups.
	 */
	public function assignUserToGroups($userId, $groupIds = null)
	{
		craft()->db->createCommand()
			->delete('usergroups_users', array('userId' => $userId));

		if ($groupIds)
		{
			if (!is_array($groupIds))
			{
				$groupIds = array($groupIds);
			}

			foreach ($groupIds as $groupId)
			{
				$values[] = array($groupId, $userId);
			}

			craft()->db->createCommand()->insertAll('usergroups_users', array('groupId', 'userId'), $values);
		}

		return true;
	}

	/**
	 * Assigns a user to the default user group.
	 *
	 * This method is called toward the end of a public registration request.
	 *
	 * @param UserModel $user The user that was just registered.
	 *
	 * @return bool Whether the user was assigned to the default group.
	 */
	public function assignUserToDefaultGroup(UserModel $user)
	{
		$defaultGroupId = craft()->systemSettings->getSetting('users', 'defaultGroup');

		if ($defaultGroupId)
		{
			// Fire an 'onBeforeAssignUserToDefaultGroup' event
			$event = new Event($this, array(
				'user'           => $user,
				'defaultGroupId' => $defaultGroupId
			));

			$this->onBeforeAssignUserToDefaultGroup($event);

			// Is the event is giving us the go-ahead?
			if ($event->performAction)
			{
				$success = $this->assignUserToGroups($user->id, array($defaultGroupId));

				if ($success)
				{
					// Fire an 'onAssignUserToDefaultGroup' event
					$this->onAssignUserToDefaultGroup(new Event($this, array(
						'user'           => $user,
						'defaultGroupId' => $defaultGroupId
					)));

					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Deletes a user group by its ID.
	 *
	 * @param int $groupId
	 *
	 * @return bool
	 */
	public function deleteGroupById($groupId)
	{
		craft()->db->createCommand()->delete('usergroups', array('id' => $groupId));
		return true;
	}

	// Events
	// -------------------------------------------------------------------------

	/**
	 * Fires an 'onBeforeAssignUserToDefaultGroup' event.
	 *
	 * @param Event $event
	 *
	 * @return null
	 */
	public function onBeforeAssignUserToDefaultGroup(Event $event)
	{
		$this->raiseEvent('onBeforeAssignUserToDefaultGroup', $event);
	}

	/**
	 * Fires an 'onAssignUserToDefaultGroup' event.
	 *
	 * @param Event $event
	 *
	 * @return null
	 */
	public function onAssignUserToDefaultGroup(Event $event)
	{
		$this->raiseEvent('onAssignUserToDefaultGroup', $event);
	}

	// Private Methods
	// =========================================================================

	/**
	 * Gets a group's record.
	 *
	 * @param int $groupId
	 *
	 * @return UserGroupRecord
	 */
	private function _getGroupRecordById($groupId = null)
	{
		if ($groupId)
		{
			$groupRecord = UserGroupRecord::model()->findById($groupId);

			if (!$groupRecord)
			{
				$this->_noGroupExists($groupId);
			}
		}
		else
		{
			$groupRecord = new UserGroupRecord();
		}

		return $groupRecord;
	}

	/**
	 * Throws a "No group exists" exception.
	 *
	 * @param int $groupId
	 *
	 * @throws Exception
	 * @return null
	 */
	private function _noGroupExists($groupId)
	{
		throw new Exception(Craft::t('No group exists with the ID “{id}”.', array('id' => $groupId)));
	}
}
