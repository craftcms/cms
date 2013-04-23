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
	 * Returns whether this element type can have thumbnails.
	 *
	 * @return bool
	 */
	public function hasThumbs()
	{
		return true;
	}

	/**
	 * Returns this element type's sources.
	 *
	 * @return array|false
	 */
	public function getSources()
	{
		$sources = array();

		if (Craft::hasPackage(CraftPackage::Users))
		{
			foreach (craft()->userGroups->getAllGroups() as $group)
			{
				$key = 'group:'.$group->id;

				$sources[$key] = array(
					'label'    => $group->name,
					'criteria' => array('groupId' => $group->id)
				);
			}
		}

		return $sources;
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
			'fullName' => Craft::t('Full Name'),
			'email'    => Craft::t('Email'),
			'status'   => Craft::t('Status'),
		);
	}

	/**
	 * Defines any custom element criteria attributes for this element type.
	 *
	 * @return array
	 */
	public function defineCriteriaAttributes()
	{
		return array(
			'groupId'       => AttributeType::Number,
			'group'         => AttributeType::Mixed,
			'username'      => AttributeType::String,
			'firstName'     => AttributeType::String,
			'lastName'      => AttributeType::String,
			'email'         => AttributeType::Email,
			'admin'         => AttributeType::Bool,
			'status'        => array(AttributeType::Enum, 'values' => array(UserStatus::Active, UserStatus::Locked, UserStatus::Suspended, UserStatus::Pending, UserStatus::Archived), 'default' => UserStatus::Active),
			'lastLoginDate' => AttributeType::DateTime,
			'order'         => array(AttributeType::String, 'default' => 'username asc')
		);
	}

	/**
	 * Modifies an entries query targeting entries of this type.
	 *
	 * @param DbCommand $query
	 * @param ElementCriteriaModel $criteria
	 * @return mixed
	 */
	public function modifyElementsQuery(DbCommand $query, ElementCriteriaModel $criteria)
	{
		Craft::requirePackage(CraftPackage::Users);

		$query
			->addSelect('users.username, users.photo, users.firstName, users.lastName, users.email, users.admin, users.status, users.lastLoginDate, users.lockoutDate')
			->join('users users', 'users.id = elements.id');
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
