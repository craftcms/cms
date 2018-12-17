<?php
namespace Craft;

/**
 * The UserElementType class is responsible for implementing and defining users as a native element type in Craft.
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://craftcms.com/license Craft License Agreement
 * @see       http://craftcms.com
 * @package   craft.app.elementtypes
 * @since     1.0
 */
class UserElementType extends BaseElementType
{
	// Public Methods
	// =========================================================================

	/**
	 * @inheritDoc IComponentType::getName()
	 *
	 * @return string
	 */
	public function getName()
	{
		return Craft::t('Users');
	}

	/**
	 * @inheritDoc IElementType::hasContent()
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
		return array(
			UserStatus::Active    => Craft::t('Active'),
			UserStatus::Pending   => Craft::t('Pending'),
			UserStatus::Locked    => Craft::t('Locked'),
			UserStatus::Suspended => Craft::t('Suspended'),
			//UserStatus::Archived  => Craft::t('Archived')
		);
	}

	/**
	 * @inheritDoc IElementType::getSources()
	 *
	 * @param string|null $context
	 *
	 * @return array|false
	 */
	public function getSources($context = null)
	{
		$sources = array(
			'*' => array(
				'label' => Craft::t('All users'),
				'hasThumbs' => true
			)
		);

		if (craft()->getEdition() == Craft::Pro)
		{
			// Admin source
			$sources['admins'] = array(
				'label' => Craft::t('Admins'),
				'criteria' => array('admin' => true),
				'hasThumbs' => true
			);

			$groups = craft()->userGroups->getAllGroups();

			if ($groups)
			{
				$sources[] = array('heading' => Craft::t('Groups'));

				foreach ($groups as $group)
				{
					$key = 'group:'.$group->id;

					$sources[$key] = array(
						'label'     => Craft::t($group->name),
						'criteria'  => array('groupId' => $group->id),
						'hasThumbs' => true
					);
				}
			}
		}

		// Allow plugins to modify the sources
		craft()->plugins->call('modifyUserSources', array(&$sources, $context));

		return $sources;
	}

	/**
	 * @inheritDoc IElementType::getAvailableActions()
	 *
	 * @param string|null $source
	 *
	 * @return array|null
	 */
	public function getAvailableActions($source = null)
	{
		$actions = array();

		// Edit
		$editAction = craft()->elements->getAction('Edit');
		$editAction->setParams(array(
			'label' => Craft::t('Edit user'),
		));
		$actions[] = $editAction;

		if (craft()->userSession->checkPermission('administrateUsers'))
		{
			// Suspend
			$actions[] = 'SuspendUsers';

			// Unsuspend
			$actions[] = 'UnsuspendUsers';
		}

		if (craft()->userSession->checkPermission('deleteUsers'))
		{
			// Delete
			$actions[] = 'DeleteUsers';
		}

		// Allow plugins to add additional actions
		$allPluginActions = craft()->plugins->call('addUserActions', array($source), true);

		foreach ($allPluginActions as $pluginActions)
		{
			$actions = array_merge($actions, $pluginActions);
		}

		return $actions;
	}

	/**
	 * @inheritDoc IElementType::defineSearchableAttributes()
	 *
	 * @return array
	 */
	public function defineSearchableAttributes()
	{
		return array('username', 'firstName', 'lastName', 'fullName', 'email');
	}

	/**
	 * @inheritDoc IElementType::defineSortableAttributes()
	 *
	 * @return array
	 */
	public function defineSortableAttributes()
	{
		if (craft()->config->get('useEmailAsUsername'))
		{
			$attributes = array(
				'email'         => Craft::t('Email'),
				'firstName'     => Craft::t('First Name'),
				'lastName'      => Craft::t('Last Name'),
				'lastLoginDate' => Craft::t('Last Login'),
				'dateCreated'   => Craft::t('Date Created'),
				'dateUpdated'   => Craft::t('Date Updated'),
			);
		}
		else
		{
			$attributes = array(
				'username'      => Craft::t('Username'),
				'firstName'     => Craft::t('First Name'),
				'lastName'      => Craft::t('Last Name'),
				'email'         => Craft::t('Email'),
				'lastLoginDate' => Craft::t('Last Login'),
				'dateCreated'   => Craft::t('Date Created'),
				'dateUpdated'   => Craft::t('Date Updated'),
			);
		}

		// Allow plugins to modify the attributes
		craft()->plugins->call('modifyUserSortableAttributes', array(&$attributes));

		return $attributes;
	}

	/**
	 * @inheritDoc IElementType::defineAvailableTableAttributes()
	 *
	 * @return array
	 */
	public function defineAvailableTableAttributes()
	{
		$attributes = array(
			'user' => array('label' => Craft::t('User')),
			'email' => array('label' => Craft::t('Email')),
			'username' => array('label' => Craft::t('Username')),
			'fullName' => array('label' => Craft::t('Full Name')),
			'firstName' => array('label' => Craft::t('First Name')),
			'lastName' => array('label' => Craft::t('Last Name')),
		);

		if (craft()->isLocalized())
		{
			$attributes['preferredLocale'] = array('label' => Craft::t('Preferred Locale'));
		}

		$attributes['id']            = array('label' => Craft::t('ID'));
		$attributes['dateCreated']   = array('label' => Craft::t('Join Date'));
		$attributes['lastLoginDate'] = array('label' => Craft::t('Last Login'));
		$attributes['dateCreated']   = array('label' => Craft::t('Date Created'));
		$attributes['dateUpdated']   = array('label' => Craft::t('Date Updated'));

		// Allow plugins to modify the attributes
		$pluginAttributes = craft()->plugins->call('defineAdditionalUserTableAttributes', array(), true);

		foreach ($pluginAttributes as $thisPluginAttributes)
		{
			$attributes = array_merge($attributes, $thisPluginAttributes);
		}

		return $attributes;
	}

	/**
	 * @inheritDoc IElementType::getDefaultTableAttributes()
	 *
	 * @param string|null $source
	 *
	 * @return array
	 */
	public function getDefaultTableAttributes($source = null)
	{
		return array('fullName', 'email', 'dateCreated', 'lastLoginDate');
	}

	/**
	 * @inheritDoc IElementType::getTableAttributeHtml()
	 *
	 * @param BaseElementModel $element
	 * @param string           $attribute
	 *
	 * @return string
	 */
	public function getTableAttributeHtml(BaseElementModel $element, $attribute)
	{
		// First give plugins a chance to set this
		$pluginAttributeHtml = craft()->plugins->callFirst('getUserTableAttributeHtml', array($element, $attribute), true);

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
					return HtmlHelper::encodeParams('<a href="mailto:{email}">{email}</a>', array('email' => $email));
				}
				else
				{
					return '';
				}
			}

			case 'preferredLocale':
			{
				$localeId = $element->preferredLocale;

				if ($localeId)
				{
					$locale = new LocaleModel($localeId);

					return $locale->getName();
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
	 * @inheritDoc IElementType::defineCriteriaAttributes()
	 *
	 * @return array
	 */
	public function defineCriteriaAttributes()
	{
		return array(
			'admin'          => AttributeType::Bool,
			'client'         => AttributeType::Bool,
			'can'            => AttributeType::String,
			'email'          => AttributeType::Email,
			'firstName'      => AttributeType::String,
			'group'          => AttributeType::Mixed,
			'groupId'        => AttributeType::Number,
			'lastName'       => AttributeType::String,
			'lastLoginDate'  => AttributeType::Mixed,
			'order'          => array(AttributeType::String, 'default' => 'username asc'),
			'preferredLocale'=> AttributeType::String,
			'status'         => array(AttributeType::Enum, 'values' => array(UserStatus::Active, UserStatus::Locked, UserStatus::Suspended, UserStatus::Pending, UserStatus::Archived), 'default' => UserStatus::Active),
			'username'       => AttributeType::String,
		);
	}

	/**
	 * @inheritDoc IElementType::getElementQueryStatusCondition()
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
				return 'users.suspended = 0 AND users.pending = 1';
			}

			case UserStatus::Locked:
			{
				return 'users.suspended = 0 AND users.locked = 1';
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
	 * @inheritDoc IElementType::modifyElementsQuery()
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

		if ($criteria->client && craft()->getEdition() == Craft::Client)
		{
			$query->andWhere(DbHelper::parseParam('users.client', $criteria->client, $query->params));
		}

		if ($criteria->can && craft()->getEdition() == Craft::Pro)
		{
			// Get the actual permission ID
			if (is_numeric($criteria->can))
			{
				$permissionId = $criteria->can;
			}
			else
			{
				$permissionId = craft()->db->createCommand()
					->select('id')
					->from('userpermissions')
					->where('name = :name', array(':name' => strtolower($criteria->can)))
					->queryScalar();
			}

			// Find the users that have that permission, either directly or through a group
			$permittedUserIds = array();

			// If the permission hasn't been assigned to any groups/users before, it won't have an ID. Don't bail
			// though, since we still want to look for admins.
			if ($permissionId)
			{
				// Get the user groups that have that permission
				$permittedGroupIds = craft()->db->createCommand()
					->select('groupId')
					->from('userpermissions_usergroups')
					->where('permissionId = :permissionId', array(':permissionId' => $permissionId))
					->queryColumn();

				if ($permittedGroupIds)
				{
					$permittedUserIds = $this->_getUserIdsByGroupIds($permittedGroupIds);
				}

				// Get the users that have that permission directly
				$permittedUserIds = array_merge(
					$permittedUserIds,
					craft()->db->createCommand()
						->select('userId')
						->from('userpermissions_users')
						->where('permissionId = :permissionId', array(':permissionId' => $permissionId))
						->queryColumn()
				);
			}

			if ($permittedUserIds)
			{
				$permissionConditions = array('or', 'users.admin = 1', array('in', 'elements.id', $permittedUserIds));
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

			$query->andWhere(array('in', 'elements.id', $userIds));
		}

		if ($criteria->group)
		{
			// Get the actual group ID(s)
			$groupIdsQuery = craft()->db->createCommand()
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

			$query->andWhere(array('in', 'elements.id', $userIds));
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
	 * @inheritDoc IElementType::populateElementModel()
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
	 * @inheritDoc IElementType::getEditorHtml()
	 *
	 * @param BaseElementModel $element
	 *
	 * @return string
	 */
	public function getEditorHtml(BaseElementModel $element)
	{
		$html = craft()->templates->render('users/_accountfields', array(
			'account'      => $element,
			'isNewAccount' => false,
			'meta'         => true,
		));

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

		return craft()->users->saveUser($element);
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
		$query = craft()->db->createCommand()
			->select('userId')
			->from('usergroups_users');

		$query->where(DbHelper::parseParam('groupId', $groupIds, $query->params));

		return $query->queryColumn();
	}
}
