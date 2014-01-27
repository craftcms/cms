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
		return array('locked',
			'suspended' => Craft::t('Suspended'),
			'pending' => Craft::t('Pending'),
			'active' => Craft::t('Active'),
			'archived' => Craft::t('Archived')
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

		if (craft()->hasPackage(CraftPackage::Users))
		{
			foreach (craft()->userGroups->getAllGroups() as $group)
			{
				$key = 'group:'.$group->id;

				$sources[$key] = array(
					'label'     => $group->name,
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
		return array(
			'username'      => Craft::t('Username'),
			'firstName'     => Craft::t('First Name'),
			'lastName'      => Craft::t('Last Name'),
			'email'         => Craft::t('Email'),
			'dateCreated'   => Craft::t('Join Date'),
			'lastLoginDate' => Craft::t('Last Login'),
		);
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
			'can'            => AttributeType::String,
			'email'          => AttributeType::Email,
			'firstName'      => AttributeType::String,
			'group'          => AttributeType::Mixed,
			'groupId'        => AttributeType::Number,
			'lastName'       => AttributeType::String,
			'lastLoginDate'  => AttributeType::DateTime,
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
			->addSelect('users.username, users.photo, users.firstName, users.lastName, users.email, users.admin, users.status, users.lastLoginDate, users.lockoutDate, users.preferredLocale')
			->join('users users', 'users.id = elements.id');

		if ($criteria->admin)
		{
			$query->andWhere(DbHelper::parseParam('users.admin', $criteria->admin, $query->params));
		}

		if ($criteria->can)
		{
			$query->leftJoin('userpermissions_users opt1_userpermissions_users', 'opt1_userpermissions_users.userId = users.id');
			$query->leftJoin('userpermissions opt1_userpermissions', 'opt1_userpermissions.id = opt1_userpermissions_users.permissionId');

			$query->leftJoin('usergroups_users opt2_usergroups_users', 'opt2_usergroups_users.userId = users.id');
			$query->leftJoin('userpermissions_usergroups opt2_userpermissions_usergroups', 'opt2_userpermissions_usergroups.groupId = opt2_usergroups_users.groupId');
			$query->leftJoin('userpermissions opt2_userpermissions', 'opt2_userpermissions.id = opt2_userpermissions_usergroups.permissionId');

			$query->andWhere(array('or',
				'users.admin = 1',
				'opt1_userpermissions.name = :permission',
				'opt2_userpermissions.name = :permission',
			), array(
				':permission' => $criteria->can
			));
		}

		if ($criteria->groupId)
		{
			$query->join('usergroups_users usergroups_users', 'usergroups_users.userId = users.id');
			$query->andWhere(DbHelper::parseParam('usergroups_users.groupId', $criteria->groupId, $query->params));
		}

		if ($criteria->group)
		{
			$query->join('usergroups_users usergroups_users', 'usergroups_users.userId = users.id');
			$query->join('usergroups usergroups', 'usergroups.id = usergroups_users.groupId');
			$query->andWhere(DbHelper::parseParam('usergroups.handle', $criteria->group, $query->params));
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
}
