<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\services;

use Craft;
use craft\app\db\Query;
use craft\app\errors\Exception;
use craft\app\events\Event;
use craft\app\elements\User;
use craft\app\models\UserGroup as UserGroupModel;
use craft\app\records\UserGroup as UserGroupRecord;
use yii\base\Component;

Craft::$app->requireEdition(Craft::Pro);

/**
 * Class UserGroups service.
 *
 * An instance of the UserGroups service is globally accessible in Craft via [[Application::userGroups `Craft::$app->getUserGroups()`]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class UserGroups extends Component
{
	/**
     * @event UserEvent The event that is triggered before a user is assigned to the default user group.
     *
     * You may set [[UserEvent::performAction]] to `false` to prevent the user from getting assigned to the default
     * user group.
     */
    const EVENT_BEFORE_ASSIGN_USER_TO_DEFAULT_GROUP = 'beforeAssignUserToDefaultGroup';

	/**
     * @event UserEvent The event that is triggered after a user is assigned to the default user group.
     */
    const EVENT_AFTER_ASSIGN_USER_TO_DEFAULT_GROUP = 'afterAssignUserToDefaultGroup';

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

		foreach ($groups as $key => $value)
		{
			$groups[$key] = UserGroupModel::create($value);
		}

		return $groups;
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
		$groupRecord = UserGroupRecord::findOne($groupId);

		if ($groupRecord)
		{
			return UserGroupModel::create($groupRecord);
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
		$groupRecord = UserGroupRecord::findOne([
			'handle' => $groupHandle
		]);

		if ($groupRecord)
		{
			return UserGroupModel::create($groupRecord);
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
		$groups = (new Query())
			->select('g.*')
			->from('{{%usergroups}} g')
			->innerJoin('{{%usergroups_users}} gu', 'gu.groupId = g.id')
			->where(['gu.userId' => $userId])
			->indexBy($indexBy)
			->all();

		foreach ($groups as $key => $value)
		{
			$groups[$key] = UserGroupModel::create($value);
		}

		return $groups;
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
		Craft::$app->getDb()->createCommand()->delete('{{%usergroups_users}}', ['userId' => $userId])->execute();

		if ($groupIds)
		{
			if (!is_array($groupIds))
			{
				$groupIds = [$groupIds];
			}

			foreach ($groupIds as $groupId)
			{
				$values[] = [$groupId, $userId];
			}

			Craft::$app->getDb()->createCommand()->batchInsert('{{%usergroups_users}}', ['groupId', 'userId'], $values)->execute();
		}

		return true;
	}

	/**
	 * Assigns a user to the default user group.
	 *
	 * This method is called toward the end of a public registration request.
	 *
	 * @param User $user The user that was just registered.
	 *
	 * @return bool Whether the user was assigned to the default group.
	 */
	public function assignUserToDefaultGroup(User $user)
	{
		$defaultGroupId = Craft::$app->getSystemSettings()->getSetting('users', 'defaultGroup');

		if ($defaultGroupId)
		{
			// Fire a 'beforeAssignUserToDefaultGroup' event
			$event = new UserEvent([
				'user' => $user
			]);

			$this->trigger(static::EVENT_BEFORE_ASSIGN_USER_TO_DEFAULT_GROUP, $event);

			// Is the event is giving us the go-ahead?
			if ($event->performAction)
			{
				$success = $this->assignUserToGroups($user->id, [$defaultGroupId]);

				if ($success)
				{
					// Fire an 'afterAssignUserToDefaultGroup' event
					$this->trigger(static::EVENT_AFTER_ASSIGN_USER_TO_DEFAULT_GROUP, new UserEvent([
						'user' => $user
					]));

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
		Craft::$app->getDb()->createCommand()->delete('{{%usergroups}}', ['id' => $groupId])->execute();
		return true;
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
			$groupRecord = UserGroupRecord::findOne($groupId);

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
		throw new Exception(Craft::t('app', 'No group exists with the ID “{id}”.', ['id' => $groupId]));
	}
}
