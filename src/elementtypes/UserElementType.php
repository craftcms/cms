<?php
namespace Craft;

/**
 * User element type
 */
class UserElementType extends BaseElementType
{
	/**
	 * Returns the element type name.
	 *
	 * @return string
	 */
	public function getName()
	{
		return Craft::t('Users');
	}

	/**
	 * Returns whether this element type has content.
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
			UserStatus::Archived  => Craft::t('Archived')
		);
	}

	/**
	 * Returns this element type's sources.
	 *
	 * @param string|null $context
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
			foreach (craft()->userGroups->getAllGroups() as $group)
			{
				$key = 'group:'.$group->id;

				$sources[$key] = array(
					'label'     => Craft::t($group->name),
					'criteria'  => array('groupId' => $group->id),
					'hasThumbs' => true
				);
			}
		}

		return $sources;
	}

	/**
	 * Defines which model attributes should be searchable.
	 *
	 * @return array
	 */
	public function defineSearchableAttributes()
	{
		return array('username', 'firstName', 'lastName', 'fullName', 'email');
	}

	/**
	 * Returns the attributes that can be shown/sorted by in table views.
	 *
	 * @param string|null $source
	 * @return array
	 */
	public function defineTableAttributes($source = null)
	{
		if (craft()->config->get('useEmailAsUsername'))
		{
			$attributes = array(
				'email'         => Craft::t('Email'),
				'firstName'     => Craft::t('First Name'),
				'lastName'      => Craft::t('Last Name'),
				'dateCreated'   => Craft::t('Join Date'),
				'lastLoginDate' => Craft::t('Last Login'),
			);
		}
		else
		{
			$attributes = array(
				'username'      => Craft::t('Username'),
				'firstName'     => Craft::t('First Name'),
				'lastName'      => Craft::t('Last Name'),
				'email'         => Craft::t('Email'),
				'dateCreated'   => Craft::t('Join Date'),
				'lastLoginDate' => Craft::t('Last Login'),
			);
		}

		return $attributes;

		return $attributes;
	}

	/**
	 * Returns the table view HTML for a given attribute.
	 *
	 * @param BaseElementModel $element
	 * @param string $attribute
	 * @return string
	 */
	public function getTableAttributeHtml(BaseElementModel $element, $attribute)
	{
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

			case 'lastLoginDate':
			{
				$date = $element->$attribute;

				if ($date)
				{
					return $date->localeDate();
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
	 * Defines any custom element criteria attributes for this element type.
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
	 * Returns the element query condition for a custom status criteria.
	 *
	 * @param DbCommand $query
	 * @param string $status
	 * @return string|false
	 */
	public function getElementQueryStatusCondition(DbCommand $query, $status)
	{
		return 'users.status = "'.$status.'"';
	}

	/**
	 * Modifies an element query targeting elements of this type.
	 *
	 * @param DbCommand $query
	 * @param ElementCriteriaModel $criteria
	 * @return mixed
	 */
	public function modifyElementsQuery(DbCommand $query, ElementCriteriaModel $criteria)
	{
		$query
			->addSelect('users.username, users.photo, users.firstName, users.lastName, users.email, users.admin, users.client, users.status, users.lastLoginDate, users.lockoutDate, users.preferredLocale')
			->join('users users', 'users.id = elements.id');

		if ($criteria->admin)
		{
			$query->andWhere(DbHelper::parseParam('users.admin', $criteria->admin, $query->params));
		}

		if ($criteria->client && craft()->getEdition() == Craft::Client)
		{
			$query->andWhere(DbHelper::parseParam('users.client', $criteria->client, $query->params));
		}

		if ($criteria->can)
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

			// Find the users that have that permission, either directly or thorugh a group
			$permittedUserIds = array();

			// If the permission hasn't been assigned to any groups/users before, it won't have an ID.
			// Don't bail though, since we still want to look for admins.
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
				$permissionConditions = array('or', 'users.admin = 1', DbHelper::parseParam('elements.id', $permittedUserIds, $query->params));
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

			// TODO: MySQL specific. Manually building the string because DbHelper::parseParam() chokes with large arrays.
			$query->andWhere('elements.id IN ('.implode(',', $userIds).')');
		}

		if ($criteria->group)
		{
			// Get the actual group ID(s)
			$groupIdsQuery = craft()->db->createCommand()
				->select('id')
				->from('usergroups');

			$groupIdsQuery->where(DbHelper::parseParam('handle', $criteria->group, $groupIdsQuery->params));
			$groupIds = $groupIdsQuery->queryColumn();

			$userIds = $this->_getUserIdsByGroupIds($groupIds);

			if (!$userIds)
			{
				return false;
			}

			// TODO: MySQL specific. Manually building the string because DbHelper::parseParam() chokes with large arrays.
			$query->andWhere('elements.id IN ('.implode(',', $userIds).')');
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
	 * Populates an element model based on a query result.
	 *
	 * @param array $row
	 * @return array
	 */
	public function populateElementModel($row)
	{
		return UserModel::populateModel($row);
	}

	/**
	 * @param $groupIds
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
