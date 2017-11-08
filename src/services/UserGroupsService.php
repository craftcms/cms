<?php
namespace Craft;

craft()->requireEdition(Craft::Pro);

/**
 * Class UserGroupsService
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://craftcms.com/license Craft License Agreement
 * @see       http://craftcms.com
 * @package   craft.app.services
 * @since     1.0
 */
class UserGroupsService extends BaseApplicationComponent
{
	// Public Methods
	// =========================================================================

	/**
	 * Returns all user groups.
	 *
	 * @param string|null $indexBy
	 *
	 * @return UserGroupModel[]
	 */
	public function getAllGroups($indexBy = null)
	{
		$groupRecords = UserGroupRecord::model()->ordered()->findAll();
		return UserGroupModel::populateModels($groupRecords, $indexBy);
	}

	/**
	 * Returns the user groups that the current user is allowed to assign to another user.
	 *
	 * @param UserModel|null $user The recipient of the user groups. If set, their current groups will be included as well.
	 *
	 * @return UserGroupModel[]
	 */
	public function getAssignableGroups(UserModel $user = null)
	{
		$currentUser = craft()->userSession->getUser();
		if (!$currentUser && !$user)
		{
			return array();
		}

		// If either user is an admin, all groups are fair game
		if (($currentUser && $currentUser->admin) || ($user && $user->admin))
		{
			return $this->getAllGroups();
		}

		$assignableGroups = array();

		foreach ($this->getAllGroups() as $group)
		{
			if (($currentUser && $currentUser->can('assignUserGroup:'.$group->id)) || ($user && $user->isInGroup($group)))
			{
				$assignableGroups[] = $group;
			}
		}

		return $assignableGroups;
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
		// Make sure $groupIds is an array
		if (!is_array($groupIds))
		{
			$groupIds = $groupIds ? array($groupIds) : array();
		}

		// Fire an 'onBeforeAssignUserToGroups' event
		$event = new Event($this, array(
			'userId'   => $userId,
			'groupIds' => $groupIds
		));

		$this->onBeforeAssignUserToGroups($event);

		if ($event->performAction)
		{
			// Delete their existing groups
			craft()->db->createCommand()->delete('usergroups_users', array('userId' => $userId));

			if ($groupIds)
			{
				// Add the new ones
				foreach ($groupIds as $groupId)
				{
					$values[] = array($groupId, $userId);
				}

				craft()->db->createCommand()->insertAll('usergroups_users', array('groupId', 'userId'), $values);
			}

			// Fire an 'onAssignUserToGroups' event
			$this->onAssignUserToGroups(new Event($this, array(
				'userId'   => $userId,
				'groupIds' => $groupIds
			)));

			// Need to invalidate the UserModel's cached values.
			$user = craft()->users->getUserById($userId);
			$userGroups = array();

			foreach ($groupIds as $groupId)
			{
				$userGroup = $this->getGroupById($groupId);

				if ($userGroup)
				{
					$userGroups[] = $userGroup;
				}
			}

			$user->setGroups($userGroups);

			return true;
		}

		return false;
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
	 * Fires an 'onBeforeAssignUserToGroups' event.
	 *
	 * @param Event $event
	 *
	 * @return null
	 */
	public function onBeforeAssignUserToGroups(Event $event)
	{
		$this->raiseEvent('onBeforeAssignUserToGroups', $event);
	}

	/**
	 * Fires an 'onAssignUserToGroups' event.
	 *
	 * @param Event $event
	 *
	 * @return null
	 */
	public function onAssignUserToGroups(Event $event)
	{
		$this->raiseEvent('onAssignUserToGroups', $event);
	}

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
