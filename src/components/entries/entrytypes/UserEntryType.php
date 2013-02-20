<?php
namespace Blocks;

/**
 * User entry type
 */
class UserEntryType extends BaseEntryType
{
	/**
	 * Returns the entry type name.
	 *
	 * @return string
	 */
	public function getName()
	{
		return Blocks::t('Users');
	}

	/**
	 * Returns the CP edit URI for a given entry.
	 *
	 * @param EntryModel $entry
	 * @return string|null
	 */
	public function getCpEditUriForEntry(EntryModel $entry)
	{
		if (Blocks::hasPackage(BlocksPackage::Users))
		{
			return 'users/'.$entry->id;
		}
		else
		{
			return false;
		}
	}

	/**
	 * Returns whether this entry type is linkable.
	 *
	 * @return bool
	 */
	public function isLinkable()
	{
		if (Blocks::hasPackage(BlocksPackage::Users))
		{
			return true;
		}
		else
		{
			return false;
		}
	}

	/**
	 * Defines any custom entry criteria attributes for this entry type.
	 *
	 * @return array
	 */
	public function defineCustomCriteriaAttributes()
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
	 * Returns the link settings HTML
	 *
	 * @return string|null
	 */
	public function getLinkSettingsHtml()
	{
		return blx()->templates->render('_components/entrytypes/User/linksettings', array(
			'settings' => $this->getLinkSettings()
		));
	}

	/**
	 * Modifies an entries query targeting entries of this type.
	 *
	 * @param DbCommand $query
	 * @param EntryCriteriaModel $criteria
	 * @return mixed
	 */
	public function modifyEntriesQuery(DbCommand $query, EntryCriteriaModel $criteria)
	{
		Blocks::requirePackage(BlocksPackage::Users);

		$query
			->addSelect('u.username, u.photo, u.firstName, u.lastName, u.email, u.admin, u.status, u.lastLoginDate')
			->join('users u', 'u.id = e.id');
	}

	/**
	 * Populates an entry model based on a query result.
	 *
	 * @param array $row
	 * @return array
	 */
	public function populateEntryModel($row)
	{
		return UserModel::populateModel($row);
	}
}
