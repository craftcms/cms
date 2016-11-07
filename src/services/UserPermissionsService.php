<?php
namespace Craft;

craft()->requireEdition(Craft::Client);

/**
 * Class UserPermissionsService
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://craftcms.com/license Craft License Agreement
 * @see       http://craftcms.com
 * @package   craft.app.services
 * @since     1.0
 */
class UserPermissionsService extends BaseApplicationComponent
{
	// Properties
	// =========================================================================

	/**
	 * @var
	 */
	private $_permissionsByGroupId;

	/**
	 * @var
	 */
	private $_permissionsByUserId;

	// Public Methods
	// =========================================================================

	/**
	 * Returns all of the known permissions, sorted by category.
	 *
	 * @return array
	 */
	public function getAllPermissions()
	{
		// General
		// ---------------------------------------------------------------------

		$general = array(
			'accessSiteWhenSystemIsOff' => array(
				'label' => Craft::t('Access the site when the system is off')
			),
			'accessCp' => array(
				'label' => Craft::t('Access the CP'),
				'nested' => array(
					'accessCpWhenSystemIsOff' => array(
						'label' => Craft::t('Access the CP when the system is off')
					),
					'performUpdates' => array(
						'label' => Craft::t('Perform Craft CMS and plugin updates')
					),
				)
			),
		);

		foreach (craft()->plugins->getPlugins() as $plugin)
		{
			if ($plugin->hasCpSection())
			{
				$general['accessCp']['nested']['accessPlugin-'.$plugin->getClassHandle()] = array(
					'label' => Craft::t('Access {plugin}', array('plugin' => $plugin->getName()))
				);
			}
		}

		$permissions[Craft::t('General')] = $general;

		// Users
		// ---------------------------------------------------------------------

		if (craft()->getEdition() == Craft::Pro)
		{
			$permissions[Craft::t('Users')] = array(
				'editUsers' => array(
					'label' => Craft::t('Edit users'),
					'nested' => array(
						'registerUsers' => array(
							'label' => Craft::t('Register users')
						),
						'assignUserPermissions' => array(
							'label' => Craft::t('Assign user groups and permissions')
						),
						'administrateUsers' => array(
							'label' => Craft::t('Administrate users'),
							'nested' => array(
								'changeUserEmails' => array(
									'label' => Craft::t('Change users’ emails')
								),
							),
						),
					),
				),
				'deleteUsers' => array(
					'label' => Craft::t('Delete users')
				),
			);
		}

		// Locales
		// ---------------------------------------------------------------------

		if (craft()->isLocalized())
		{
			$label = Craft::t('Locales');
			$locales = craft()->i18n->getSiteLocales();

			foreach ($locales as $locale)
			{
				$permissions[$label]['editLocale:'.$locale->getId()] = array(
					'label' => $locale->getName()
				);
			}
		}

		// Entries
		// ---------------------------------------------------------------------

		$sections = craft()->sections->getAllSections();

		foreach ($sections as $section)
		{
			$label = Craft::t('Section - {section}', array('section' => Craft::t($section->name)));

			if ($section->type == SectionType::Single)
			{
				$permissions[$label] = $this->_getSingleEntryPermissions($section);
			}
			else
			{
				$permissions[$label] = $this->_getEntryPermissions($section);
			}
		}

		// Global sets
		// ---------------------------------------------------------------------

		$globalSets = craft()->globals->getAllSets();

		if ($globalSets)
		{
			$permissions[Craft::t('Global Sets')] = $this->_getGlobalSetPermissions($globalSets);
		}

		// Categories
		// ---------------------------------------------------------------------

		$categoryGroups = craft()->categories->getAllGroups();

		if ($categoryGroups)
		{
			$permissions[Craft::t('Categories')] = $this->_getCategoryGroupPermissions($categoryGroups);
		}

		// Asset sources
		// ---------------------------------------------------------------------

		$assetSources = craft()->assetSources->getAllSources();

		foreach ($assetSources as $source)
		{
			$label = Craft::t('Asset Source - {source}', array('source' => Craft::t($source->name)));
			$permissions[$label] = $this->_getAssetSourcePermissions($source->id);
		}

		// Plugins
		// ---------------------------------------------------------------------

		foreach (craft()->plugins->call('registerUserPermissions') as $pluginHandle => $pluginPermissions)
		{
			$plugin = craft()->plugins->getPlugin($pluginHandle);
			$permissions[$plugin->getName()] = $pluginPermissions;
		}

		return $permissions;
	}

	/**
	 * Returns all of a given user group's permissions.
	 *
	 * @param int $groupId
	 *
	 * @return array
	 */
	public function getPermissionsByGroupId($groupId)
	{
		if (!isset($this->_permissionsByUserId[$groupId]))
		{
			$groupPermissions = craft()->db->createCommand()
				->select('p.name')
				->from('userpermissions p')
				->join('userpermissions_usergroups p_g', 'p_g.permissionId = p.id')
				->where(array('p_g.groupId' => $groupId))
				->queryColumn();

			$this->_permissionsByGroupId[$groupId] = $groupPermissions;
		}

		return $this->_permissionsByGroupId[$groupId];
	}

	/**
	 * Returns all of the group permissions a given user has.
	 *
	 * @param int $userId
	 *
	 * @return array
	 */
	public function getGroupPermissionsByUserId($userId)
	{
		return craft()->db->createCommand()
			->select('p.name')
			->from('userpermissions p')
			->join('userpermissions_usergroups p_g', 'p_g.permissionId = p.id')
			->join('usergroups_users g_u', 'g_u.groupId = p_g.groupId')
			->where(array('g_u.userId' => $userId))
			->queryColumn();
	}

	/**
	 * Returns whether a given user group has a given permission.
	 *
	 * @param int    $groupId
	 * @param string $checkPermission
	 *
	 * @return bool
	 */
	public function doesGroupHavePermission($groupId, $checkPermission)
	{
		$allPermissions = $this->getPermissionsByGroupId($groupId);
		$checkPermission = strtolower($checkPermission);

		return in_array($checkPermission, $allPermissions);
	}

	/**
	 * Saves new permissions for a user group.
	 *
	 * @param int   $groupId
	 * @param array $permissions
	 *
	 * @return bool
	 */
	public function saveGroupPermissions($groupId, $permissions)
	{
		// Delete any existing group permissions
		craft()->db->createCommand()
			->delete('userpermissions_usergroups', array('groupId' => $groupId));

		$permissions = $this->_filterOrphanedPermissions($permissions);

		if ($permissions)
		{
			$groupPermissionVals = array();

			foreach ($permissions as $permissionName)
			{
				$permissionRecord = $this->_getPermissionRecordByName($permissionName);
				$groupPermissionVals[] = array($permissionRecord->id, $groupId);
			}

			// Add the new group permissions
			craft()->db->createCommand()
				->insertAll('userpermissions_usergroups', array('permissionId', 'groupId'), $groupPermissionVals);
		}

		return true;
	}

	/**
	 * Returns all of a given user's permissions.
	 *
	 * @param int $userId
	 *
	 * @return array
	 */
	public function getPermissionsByUserId($userId)
	{
		if (!isset($this->_permissionsByUserId[$userId]))
		{
			$groupPermissions = $this->getGroupPermissionsByUserId($userId);

			$userPermissions = craft()->db->createCommand()
				->select('p.name')
				->from('userpermissions p')
				->join('userpermissions_users p_u', 'p_u.permissionId = p.id')
				->where(array('p_u.userId' => $userId))
				->queryColumn();

			$this->_permissionsByUserId[$userId] = array_unique(array_merge($groupPermissions, $userPermissions));
		}

		return $this->_permissionsByUserId[$userId];
	}

	/**
	 * Returns whether a given user has a given permission.
	 *
	 * @param int    $userId
	 * @param string $checkPermission
	 *
	 * @return bool
	 */
	public function doesUserHavePermission($userId, $checkPermission)
	{
		$allPermissions = $this->getPermissionsByUserId($userId);
		$checkPermission = strtolower($checkPermission);

		return in_array($checkPermission, $allPermissions);
	}

	/**
	 * Saves new permissions for a user.
	 *
	 * @param int   $userId
	 * @param array $permissions
	 *
	 * @return bool
	 */
	public function saveUserPermissions($userId, $permissions)
	{
		// Delete any existing user permissions
		craft()->db->createCommand()
			->delete('userpermissions_users', array('userId' => $userId));

		// Filter out any orphaned permissions
		$groupPermissions = $this->getGroupPermissionsByUserId($userId);
		$permissions = $this->_filterOrphanedPermissions($permissions, $groupPermissions);

		if ($permissions)
		{
			$userPermissionVals = array();

			foreach ($permissions as $permissionName)
			{
				$permissionRecord = $this->_getPermissionRecordByName($permissionName);
				$userPermissionVals[] = array($permissionRecord->id, $userId);
			}

			// Add the new user permissions
			craft()->db->createCommand()
				->insertAll('userpermissions_users', array('permissionId', 'userId'), $userPermissionVals);
		}

		return true;
	}

	// Private Methods
	// =========================================================================

	/**
	 * Returns the entry permissions for a given Single section.
	 *
	 * @param SectionModel $section
	 *
	 * @return array
	 */
	private function _getSingleEntryPermissions($section)
	{
		$suffix = ':'.$section->id;

		return array(
			"editEntries{$suffix}" => array(
				'label' => Craft::t('Edit “{title}”', array('title' => Craft::t($section->name))),
				'nested' => array(
					"publishEntries{$suffix}" => array(
						'label' => Craft::t('Publish live changes')
					),
					"editPeerEntryDrafts{$suffix}" => array(
						'label' => Craft::t('Edit other authors’ drafts'),
						'nested' => array(
							"publishPeerEntryDrafts{$suffix}" => array(
								'label' => Craft::t('Publish other authors’ drafts')
							),
							"deletePeerEntryDrafts{$suffix}" => array(
								'label' => Craft::t('Delete other authors’ drafts')
							),
						)
					),
				)
			)
		);
	}

	/**
	 * Returns the entry permissions for a given Channel or Structure section.
	 *
	 * @param SectionModel $section
	 *
	 * @return array
	 */
	private function _getEntryPermissions($section)
	{
		$suffix = ':'.$section->id;

		return array(
			"editEntries{$suffix}" => array(
				'label' => Craft::t('Edit entries'),
				'nested' => array(
					"createEntries{$suffix}" => array(
						'label' => Craft::t('Create entries'),
					),
					"publishEntries{$suffix}" => array(
						'label' => Craft::t('Publish live changes')
					),
					"deleteEntries{$suffix}" => array(
						'label' => Craft::t('Delete entries')
					),
					"editPeerEntries{$suffix}" => array(
						'label' => Craft::t('Edit other authors’ entries'),
						'nested' => array(
							"publishPeerEntries{$suffix}" => array(
								'label' => Craft::t('Publish live changes for other authors’ entries')
							),
							"deletePeerEntries{$suffix}" => array(
								'label' => Craft::t('Delete other authors’ entries')
							),
						)
					),
					"editPeerEntryDrafts{$suffix}" => array(
						'label' => Craft::t('Edit other authors’ drafts'),
						'nested' => array(
							"publishPeerEntryDrafts{$suffix}" => array(
								'label' => Craft::t('Publish other authors’ drafts')
							),
							"deletePeerEntryDrafts{$suffix}" => array(
								'label' => Craft::t('Delete other authors’ drafts')
							),
						)
					),
				)
			)
		);
	}

	/**
	 * Returns the global set permissions.
	 *
	 * @param array $globalSets
	 *
	 * @return array
	 */
	private function _getGlobalSetPermissions($globalSets)
	{
		$permissions = array();

		foreach ($globalSets as $globalSet)
		{
			$permissions['editGlobalSet:'.$globalSet->id] = array(
				'label' => Craft::t('Edit “{title}”', array('title' => Craft::t($globalSet->name)))
			);
		}

		return $permissions;
	}

	/**
	 * Returns the category permissions.
	 *
	 * @param $groups
	 *
	 * @return array
	 */
	private function _getCategoryGroupPermissions($groups)
	{
		$permissions = array();

		foreach ($groups as $group)
		{
			$permissions['editCategories:'.$group->id] = array(
				'label' => Craft::t('Edit “{title}”', array('title' => Craft::t($group->name)))
			);
		}

		return $permissions;
	}

	/**
	 * Returns the array source permissions.
	 *
	 * @param int $sourceId
	 *
	 * @return array
	 */
	private function _getAssetSourcePermissions($sourceId)
	{
		$suffix = ':'.$sourceId;

		return array(
			"viewAssetSource{$suffix}" => array(
				'label' => Craft::t('View source'),
				'nested' => array(
					"uploadToAssetSource{$suffix}" => array(
						'label' => Craft::t('Upload files'),
					),
					"createSubfoldersInAssetSource{$suffix}" => array(
						'label' => Craft::t('Create subfolders'),
					),
					"removeFromAssetSource{$suffix}" => array(
						'label' => Craft::t('Remove files'),
					)
				)
			)
		);
	}

	/**
	 * Filters out any orphaned permissions.
	 *
	 * @param array $postedPermissions The posted permissions.
	 * @param array $groupPermissions  Permissions the user is already assigned to via their group, if we're saving a
	 *                                 user's permissions.
	 *
	 * @return array $filteredPermissions The permissions we'll actually let them save.
	 */
	private function _filterOrphanedPermissions($postedPermissions, $groupPermissions = array())
	{
		$filteredPermissions = array();

		if ($postedPermissions)
		{
			foreach ($this->getAllPermissions() as $categoryPermissions)
			{
				$this->_findSelectedPermissions($categoryPermissions, $postedPermissions, $groupPermissions, $filteredPermissions);
			}
		}

		return $filteredPermissions;
	}

	/**
	 * Iterates through a group of permissions, returning the ones that were selected.
	 *
	 * @param array $permissionsGroup
	 * @param array $postedPermissions
	 * @param array $groupPermissions
	 * @param array &$filteredPermissions
	 *
	 * @return boolean Whether any permissions were added to $filteredPermissions
	 */
	private function _findSelectedPermissions($permissionsGroup, $postedPermissions, $groupPermissions, &$filteredPermissions)
	{
		$hasAssignedPermissions = false;

		foreach ($permissionsGroup as $name => $data)
		{
			// Should the user have this permission (either directly or via their group)?
			if (($inPostedPermissions = in_array($name, $postedPermissions)) || in_array(strtolower($name), $groupPermissions))
			{
				// First assign any nested permissions
				if (!empty($data['nested']))
				{
					$hasAssignedNestedPermissions = $this->_findSelectedPermissions($data['nested'], $postedPermissions, $groupPermissions, $filteredPermissions);
				}
				else
				{
					$hasAssignedNestedPermissions = false;
				}

				// Were they assigned this permission (or any of its nested permissions) directly?
				if ($inPostedPermissions || $hasAssignedNestedPermissions)
				{
					// Assign the permission directly to the user
					$filteredPermissions[] = $name;
					$hasAssignedPermissions = true;
				}
			}
		}

		return $hasAssignedPermissions;
	}

	/**
	 * Returns a permission record based on its name. If a record doesn't exist, it will be created.
	 *
	 * @param string $permissionName
	 *
	 * @return UserPermissionRecord
	 */
	private function _getPermissionRecordByName($permissionName)
	{
		// Permission names are always stored in lowercase
		$permissionName = strtolower($permissionName);

		$permissionRecord = UserPermissionRecord::model()->findByAttributes(array(
			'name' => $permissionName
		));

		if (!$permissionRecord)
		{
			$permissionRecord = new UserPermissionRecord();
			$permissionRecord->name = $permissionName;
			$permissionRecord->save();
		}

		return $permissionRecord;
	}
}
