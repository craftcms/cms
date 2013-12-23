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
			'groupId'        => AttributeType::Number,
			'group'          => AttributeType::Mixed,
			'username'       => AttributeType::String,
			'firstName'      => AttributeType::String,
			'lastName'       => AttributeType::String,
			'email'          => AttributeType::Email,
			'admin'          => AttributeType::Bool,
			'status'         => array(AttributeType::Enum, 'values' => array(UserStatus::Active, UserStatus::Locked, UserStatus::Suspended, UserStatus::Pending, UserStatus::Archived), 'default' => UserStatus::Active),
			'lastLoginDate'  => AttributeType::DateTime,
			'order'          => array(AttributeType::String, 'default' => 'username asc'),
			'preferredLocale'=> AttributeType::String,
		);
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

		if ($criteria->groupId)
		{
			$query->join('usergroups_users usergroups_users', 'users.id = usergroups_users.userId');
			$query->andWhere(DbHelper::parseParam('usergroups_users.groupId', $criteria->groupId, $query->params));
		}

		if ($criteria->group)
		{
			$query->join('usergroups_users usergroups_users', 'users.id = usergroups_users.userId');
			$query->join('usergroups usergroups', 'usergroups_users.groupId = usergroups.id');
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

		if ($criteria->status)
		{
			$query->andWhere(DbHelper::parseParam('users.status', $criteria->status, $query->params));
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
