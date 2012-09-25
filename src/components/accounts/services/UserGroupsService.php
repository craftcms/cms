<?php
namespace Blocks;

/**
 *
 */
class UserGroupsService extends BaseApplicationComponent
{
	/**
	 * Populates a user group package.
	 *
	 * @param array|UserGroupRecord $attributes
	 * @return UserGroupPackage
	 */
	public function populateGroupPackage($attributes)
	{
		if ($attributes instanceof UserGroupRecord)
		{
			$attributes = $attributes->getAttributes();
		}

		$group = new UserGroupPackage();

		$group->id = $attributes['id'];
		$group->name = $attributes['name'];
		$group->handle = $attributes['handle'];

		return $group;
	}

	/**
	 * Mass-populates user group packages.
	 *
	 * @param array  $data
	 * @param string $index
	 * @return array
	 */
	public function populateGroupPackages($data, $index = 'id')
	{
		$groupPackages = array();

		foreach ($data as $attributes)
		{
			$group = $this->populateGroupPackage($attributes);
			$groupPackages[$group->$index] = $group;
		}

		return $groupPackages;
	}

	/**
	 * Returns all user groups.
	 *
	 * @return array
	 */
	public function getAllGroups()
	{
		$groupRecords = UserGroupRecord::model()->findAll();
		return $this->populateGroupPackages($groupRecords);
	}

	/**
	 * Gets a user group by its ID.
	 *
	 * @param int $groupId
	 * @return UserGroupPackage
	 */
	public function getGroupById($groupId)
	{
		$groupRecord = UserGroupRecord::model()->findById($groupId);
		if ($groupRecord)
		{
			return $this->populateGroupPackage($groupRecord);
		}
	}

	/**
	 * Saves a user group.
	 *
	 * @param UserGroupPackage $group
	 * @return bool
	 */
	public function saveGroup(UserGroupPackage $group)
	{
		$groupRecord = $this->_getGroupRecordById($group->id);

		$groupRecord->name = $group->name;
		$groupRecord->handle = $group->handle;

		if ($groupRecord->save())
		{
			// Now that we have a group ID, save it on the package
			if (!$group->id)
			{
				$group->id = $groupRecord->id;
			}

			return true;
		}
		else
		{
			$group->errors = $groupRecord->getErrors();

			return false;
		}
	}

	/**
	 * Deletes a user group by its ID.
	 *
	 * @param int $groupId
	 * @throws \Exception
	 * @return bool
	 */
	public function deleteGroupById($groupId)
	{
		$groupRecord = $this->_getGroupRecordById($groupId);
		$group = $this->populateGroupPackage($groupRecord);

		$transaction = blx()->db->beginTransaction();
		try
		{
			$groupRecord->delete();
			$transaction->commit();
		}
		catch (\Exception $e)
		{
			$transaction->rollBack();
			throw $e;
		}

		return true;
	}

	/**
	 * Gets a group's record.
	 *
	 * @access private
	 * @param int $groupId
	 * @return UserGroupRecord
	 */
	private function _getGroupRecordById($groupId = null)
	{
		$userId = blx()->accounts->getCurrentUser()->id;

		if ($groupId)
		{
			$groupRecord = UserGroupRecord::model()->findById($groupId);

			if (!$groupRecord)
				$this->_noGroupExists($groupId);
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
	 * @access private
	 * @param int $groupId
	 * @throws Exception
	 */
	private function _noGroupExists($groupId)
	{
		throw new Exception(Blocks::t('No group exists with the ID “{id}”', array('id' => $groupId)));
	}
}
