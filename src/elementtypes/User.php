<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\elementtypes;

use Craft;
use craft\app\db\DbCommand;
use craft\app\enums\AttributeType;
use craft\app\enums\UserStatus;
use craft\app\helpers\DbHelper;
use craft\app\models\BaseElementModel;
use craft\app\models\ElementCriteria as ElementCriteriaModel;
use craft\app\models\User as UserModel;

/**
 * The User class is responsible for implementing and defining users as a native element type in Craft.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class User extends BaseElementType
{
	// Public Methods
	// =========================================================================

	/**
	 * @inheritDoc ComponentTypeInterface::getName()
	 *
	 * @return string
	 */
	public function getName()
	{
		return Craft::t('Users');
	}

	/**
	 * @inheritDoc ElementTypeInterface::hasContent()
	 *
	 * @return bool
	 */
	public function hasContent()
	{
		return true;
	}

	/**
	 * Returns whether this element type can have statuses.
	 *
	 * @return bool
	 */
	public function hasStatuses()
	{
		return true;
	}

	/**
	 * Returns all of the possible statuses that elements of this type may have.
	 *
	 * @return array|null
	 */
	public function getStatuses()
	{
		return [
			UserStatus::Active    => Craft::t('Active'),
			UserStatus::Pending   => Craft::t('Pending'),
			UserStatus::Locked    => Craft::t('Locked'),
			UserStatus::Suspended => Craft::t('Suspended'),
			UserStatus::Archived  => Craft::t('Archived')
		];
	}

	/**
	 * @inheritDoc ElementTypeInterface::getSources()
	 *
	 * @param string|null $context
	 *
	 * @return array|false
	 */
	public function getSources($context = null)
	{
		$sources = [
			'*' => [
				'label' => Craft::t('All users'),
				'hasThumbs' => true
			]
		];

		if (Craft::$app->getEdition() == Craft::Pro)
		{
			foreach (Craft::$app->userGroups->getAllGroups() as $group)
			{
				$key = 'group:'.$group->id;

				$sources[$key] = [
					'label'     => Craft::t($group->name),
					'criteria'  => ['groupId' => $group->id],
					'hasThumbs' => true
				];
			}
		}

		return $sources;
	}

	/**
	 * @inheritDoc ElementTypeInterface::getAvailableActions()
	 *
	 * @param string|null $source
	 *
	 * @return array|null
	 */
	public function getAvailableActions($source = null)
	{
		$actions = [];

		// Edit
		$editAction = Craft::$app->elements->getAction('Edit');
		$editAction->setParams([
			'label' => Craft::t('Edit user'),
		]);
		$actions[] = $editAction;

		if (Craft::$app->getUser()->checkPermission('administrateUsers'))
		{
			// Suspend
			$actions[] = 'SuspendUsers';

			// Unsuspend
			$actions[] = 'UnsuspendUsers';
		}

		if (Craft::$app->getUser()->checkPermission('deleteUsers'))
		{
			// Delete
			$actions[] = 'DeleteUsers';
		}

		// Allow plugins to add additional actions
		$allPluginActions = Craft::$app->plugins->call('addUserActions', [$source], true);

		foreach ($allPluginActions as $pluginActions)
		{
			$actions = array_merge($actions, $pluginActions);
		}

		return $actions;
	}

	/**
	 * @inheritDoc ElementTypeInterface::defineSearchableAttributes()
	 *
	 * @return array
	 */
	public function defineSearchableAttributes()
	{
		return ['username', 'firstName', 'lastName', 'fullName', 'email'];
	}

	/**
	 * @inheritDoc ElementTypeInterface::defineSortableAttributes()
	 *
	 * @retrun array
	 */
	public function defineSortableAttributes()
	{
		if (Craft::$app->config->get('useEmailAsUsername'))
		{
			$attributes = [
				'email'         => Craft::t('Email'),
				'firstName'     => Craft::t('First Name'),
				'lastName'      => Craft::t('Last Name'),
				'dateCreated'   => Craft::t('Join Date'),
				'lastLoginDate' => Craft::t('Last Login'),
			];
		}
		else
		{
			$attributes = [
				'username'      => Craft::t('Username'),
				'firstName'     => Craft::t('First Name'),
				'lastName'      => Craft::t('Last Name'),
				'email'         => Craft::t('Email'),
				'dateCreated'   => Craft::t('Join Date'),
				'lastLoginDate' => Craft::t('Last Login'),
			];
		}

		// Allow plugins to modify the attributes
		Craft::$app->plugins->call('modifyUserSortableAttributes', [&$attributes]);

		return $attributes;
	}

	/**
	 * @inheritDoc ElementTypeInterface::defineTableAttributes()
	 *
	 * @param string|null $source
	 *
	 * @return array
	 */
	public function defineTableAttributes($source = null)
	{
		if (Craft::$app->config->get('useEmailAsUsername'))
		{
			$attributes = [
				'email'         => Craft::t('Email'),
				'firstName'     => Craft::t('First Name'),
				'lastName'      => Craft::t('Last Name'),
				'dateCreated'   => Craft::t('Join Date'),
				'lastLoginDate' => Craft::t('Last Login'),
			];
		}
		else
		{
			$attributes = [
				'username'      => Craft::t('Username'),
				'firstName'     => Craft::t('First Name'),
				'lastName'      => Craft::t('Last Name'),
				'email'         => Craft::t('Email'),
				'dateCreated'   => Craft::t('Join Date'),
				'lastLoginDate' => Craft::t('Last Login'),
			];
		}

		// Allow plugins to modify the attributes
		Craft::$app->plugins->call('modifyUserTableAttributes', [&$attributes, $source]);

		return $attributes;
	}

	/**
	 * @inheritDoc ElementTypeInterface::getTableAttributeHtml()
	 *
	 * @param BaseElementModel $element
	 * @param string           $attribute
	 *
	 * @return string
	 */
	public function getTableAttributeHtml(BaseElementModel $element, $attribute)
	{
		// First give plugins a chance to set this
		$pluginAttributeHtml = Craft::$app->plugins->callFirst('getUserTableAttributeHtml', [$element, $attribute], true);

		if ($pluginAttributeHtml !== null)
		{
			return $pluginAttributeHtml;
		}

		switch ($attribute)
		{
			case 'email':
			{
				$email = $element->email;

				if ($email)
				{
					return '<a href="mailto:'.$email.'">'.$email.'</a>';
				}
				else
				{
					return '';
				}
			}

			default:
			{
				return parent::getTableAttributeHtml($element, $attribute);
			}
		}
	}

	/**
	 * @inheritDoc ElementTypeInterface::defineCriteriaAttributes()
	 *
	 * @return array
	 */
	public function defineCriteriaAttributes()
	{
		return [
			'admin'          => AttributeType::Bool,
			'client'         => AttributeType::Bool,
			'can'            => AttributeType::String,
			'email'          => AttributeType::Email,
			'firstName'      => AttributeType::String,
			'group'          => AttributeType::Mixed,
			'groupId'        => AttributeType::Number,
			'lastName'       => AttributeType::String,
			'lastLoginDate'  => AttributeType::Mixed,
			'order'          => [AttributeType::String, 'default' => 'username asc'],
			'preferredLocale'=> AttributeType::String,
			'status'         => [AttributeType::Enum, 'values' => [UserStatus::Active, UserStatus::Locked, UserStatus::Suspended, UserStatus::Pending, UserStatus::Archived], 'default' => UserStatus::Active],
			'username'       => AttributeType::String,
		];
	}

	/**
	 * @inheritDoc ElementTypeInterface::getElementQueryStatusCondition()
	 *
	 * @param DbCommand $query
	 * @param string    $status
	 *
	 * @return string|false
	 */
	public function getElementQueryStatusCondition(DbCommand $query, $status)
	{
		switch ($status)
		{
			case UserStatus::Active:
			{
				return 'users.archived = 0 AND users.suspended = 0 AND users.locked = 0 and users.pending = 0';
			}

			case UserStatus::Pending:
			{
				return 'users.pending = 1';
			}

			case UserStatus::Locked:
			{
				return 'users.locked = 1';
			}

			case UserStatus::Suspended:
			{
				return 'users.suspended = 1';
			}

			case UserStatus::Archived:
			{
				return 'users.archived = 1';
			}
		}
	}

	/**
	 * @inheritDoc ElementTypeInterface::modifyElementsQuery()
	 *
	 * @param DbCommand            $query
	 * @param ElementCriteriaModel $criteria
	 *
	 * @return mixed
	 */
	public function modifyElementsQuery(DbCommand $query, ElementCriteriaModel $criteria)
	{
		$query
			->addSelect('users.username, users.photo, users.firstName, users.lastName, users.email, users.admin, users.client, users.locked, users.pending, users.suspended, users.archived, users.lastLoginDate, users.lockoutDate, users.preferredLocale')
			->join('users users', 'users.id = elements.id');

		if ($criteria->admin)
		{
			$query->andWhere(DbHelper::parseParam('users.admin', $criteria->admin, $query->params));
		}

		if ($criteria->client && Craft::$app->getEdition() == Craft::Client)
		{
			$query->andWhere(DbHelper::parseParam('users.client', $criteria->client, $query->params));
		}

		if ($criteria->can && Craft::$app->getEdition() == Craft::Pro)
		{
			// Get the actual permission ID
			if (is_numeric($criteria->can))
			{
				$permissionId = $criteria->can;
			}
			else
			{
				$permissionId = Craft::$app->getDb()->createCommand()
					->select('id')
					->from('userpermissions')
					->where('name = :name', [':name' => strtolower($criteria->can)])
					->queryScalar();
			}

			// Find the users that have that permission, either directly or through a group
			$permittedUserIds = [];

			// If the permission hasn't been assigned to any groups/users before, it won't have an ID. Don't bail
			// though, since we still want to look for admins.
			if ($permissionId)
			{
				// Get the user groups that have that permission
				$permittedGroupIds = Craft::$app->getDb()->createCommand()
					->select('groupId')
					->from('userpermissions_usergroups')
					->where('permissionId = :permissionId', [':permissionId' => $permissionId])
					->queryColumn();

				if ($permittedGroupIds)
				{
					$permittedUserIds = $this->_getUserIdsByGroupIds($permittedGroupIds);
				}

				// Get the users that have that permission directly
				$permittedUserIds = array_merge(
					$permittedUserIds,
					Craft::$app->getDb()->createCommand()
						->select('userId')
						->from('userpermissions_users')
						->where('permissionId = :permissionId', [':permissionId' => $permissionId])
						->queryColumn()
				);
			}

			if ($permittedUserIds)
			{
				$permissionConditions = ['or', 'users.admin = 1', ['in', 'elements.id', $permittedUserIds]];
			}
			else
			{
				$permissionConditions = 'users.admin = 1';
			}

			$query->andWhere($permissionConditions);
		}

		if ($criteria->groupId)
		{
			$userIds = $this->_getUserIdsByGroupIds($criteria->groupId);

			if (!$userIds)
			{
				return false;
			}

			$query->andWhere(['in', 'elements.id', $userIds]);
		}

		if ($criteria->group)
		{
			// Get the actual group ID(s)
			$groupIdsQuery = Craft::$app->getDb()->createCommand()
				->select('id')
				->from('usergroups');

			$groupIdsQuery->where(DbHelper::parseParam('handle', $criteria->group, $groupIdsQuery->params));
			$groupIds = $groupIdsQuery->queryColumn();

			// In the case where the group doesn't exist.
			if (!$groupIds)
			{
				return false;
			}

			$userIds = $this->_getUserIdsByGroupIds($groupIds);

			// In case there are no users in the groups.
			if (!$userIds)
			{
				return false;
			}

			$query->andWhere(['in', 'elements.id', $userIds]);
		}

		if ($criteria->username)
		{
			$query->andWhere(DbHelper::parseParam('users.username', $criteria->username, $query->params));
		}

		if ($criteria->firstName)
		{
			$query->andWhere(DbHelper::parseParam('users.firstName', $criteria->firstName, $query->params));
		}

		if ($criteria->lastName)
		{
			$query->andWhere(DbHelper::parseParam('users.lastName', $criteria->lastName, $query->params));
		}

		if ($criteria->email)
		{
			$query->andWhere(DbHelper::parseParam('users.email', $criteria->email, $query->params));
		}

		if ($criteria->preferredLocale)
		{
			$query->andWhere(DbHelper::parseParam('users.preferredLocale', $criteria->preferredLocale, $query->params));
		}

		if ($criteria->lastLoginDate)
		{
			$query->andWhere(DbHelper::parseDateParam('users.lastLoginDate', $criteria->lastLoginDate, $query->params));
		}
	}

	/**
	 * @inheritDoc ElementTypeInterface::populateElementModel()
	 *
	 * @param array $row
	 *
	 * @return array
	 */
	public function populateElementModel($row)
	{
		return UserModel::populateModel($row);
	}

	/**
	 * @inheritDoc ElementTypeInterface::getEditorHtml()
	 *
	 * @param BaseElementModel $element
	 *
	 * @return string
	 */
	public function getEditorHtml(BaseElementModel $element)
	{
		$html = Craft::$app->templates->render('users/_accountfields', [
			'account'      => $element,
			'isNewAccount' => false,
		]);

		$html .= parent::getEditorHtml($element);

		return $html;
	}

	/**
	 * @inheritdoc BaseElementType::saveElement()
	 *
	 * @return bool
	 */
	public function saveElement(BaseElementModel $element, $params)
	{
		if (isset($params['username']))
		{
			$element->username = $params['username'];
		}

		if (isset($params['firstName']))
		{
			$element->firstName = $params['firstName'];
		}

		if (isset($params['lastName']))
		{
			$element->lastName = $params['lastName'];
		}

		return Craft::$app->users->saveUser($element);
	}

	// Private Methods
	// =========================================================================

	/**
	 * @param $groupIds
	 *
	 * @return array
	 */
	private function _getUserIdsByGroupIds($groupIds)
	{
		$query = Craft::$app->getDb()->createCommand()
			->select('userId')
			->from('usergroups_users');

		$query->where(DbHelper::parseParam('groupId', $groupIds, $query->params));

		return $query->queryColumn();
	}
}
