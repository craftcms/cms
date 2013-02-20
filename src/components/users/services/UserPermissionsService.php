<?php
namespace Blocks;

/**
 *
 */
class UserPermissionsService extends BaseApplicationComponent
{
	private $_permissionsByGroupId;
	private $_permissionsByUserId;

	/**
	 * Returns all of the known permissions, sorted by category.
	 *
	 * @return array
	 */
	public function getAllPermissions()
	{
		// General

		$general = array(
			'accessSiteWhenSystemIsOff' => array(
				'label' => Blocks::t('Access the site when the system is off')
			),
			'accessCp' => array(
				'label' => 'Access the CP',
				'nested' => array(
					'accessCpWhenSystemIsOff' => array(
						'label' => Blocks::t('Access the CP when the system is off')
					),
					'autoUpdateBlocks' => array(
						'label' => Blocks::t('Auto-update Blocks')
					),
				)
			),
		);

		foreach (blx()->plugins->getPlugins() as $plugin)
		{
			if ($plugin->hasCpSection())
			{
				$general['accessCp']['nested']['accessPlugin-'.$plugin->getClassHandle()] = array(
					'label' => Blocks::t('Access {plugin}', array('plugin' => $plugin->getName()))
				);
			}
		}

		$permissions[Blocks::t('General')] = $general;

		// Users

		$permissions[Blocks::t('Users')] = array(
			'editUsers' => array(
				'label' => Blocks::t('Edit users'),
				'nested' => array(
					'registerUsers' => array(
						'label' => Blocks::t('Register users')
					),
					'administrateUsers' => array(
						'label' => Blocks::t('Administrate users')
					)
				),
			),
		);

		// Entries

		$sections = blx()->sections->getAllSections();

		foreach ($sections as $section)
		{
			$label = Blocks::t('Section - {section}', array('section' => Blocks::t($section->name)));
			$permissions[$label] = $this->_getEntryPermissions($section->id);
		}

		// Singletons

		$singletons = blx()->singletons->getAllSingletons();

		if ($singletons)
		{
			$permissions[Blocks::t('Singletons')] = $this->_getSingletonPermissions($singletons);
		}

		// Globals

		$permissions[Blocks::t('Globals')] = array(
			'editGlobals' => array(
				'label' => Blocks::t('Edit globals')
			)
		);

		// Plugins

		foreach (blx()->plugins->callHook('registerUserPermissions') as $pluginHandle => $pluginPermissions)
		{
			$plugin = blx()->plugins->getPlugin($pluginHandle);
			$permissions[$plugin->getName()] = $pluginPermissions;
		}

		return $permissions;
	}

	/**
	 * Returns all of a given user group's permissions.
	 *
	 * @param int $groupId
	 * @return array
	 */
	public function getPermissionsByGroupId($groupId)
	{
		if (!isset($this->_permissionsByUserId[$groupId]))
		{
			$groupPermissions = blx()->db->createCommand()
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
	 * @return array
	 */
	public function getGroupPermissionsByUserId($userId)
	{
		return blx()->db->createCommand()
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
	 * @param int $groupId
	 * @param string $checkPermission
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
	 * @param int $groupId
	 * @param array $permissions
	 * @return bool
	 */
	public function saveGroupPermissions($groupId, $permissions)
	{
		// Delete any existing group permissions
		blx()->db->createCommand()
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
			blx()->db->createCommand()
				->insertAll('userpermissions_usergroups', array('permissionId', 'groupId'), $groupPermissionVals);
		}

		return true;
	}

	/**
	 * Returns all of a given user's permissions.
	 *
	 * @param int $userId
	 * @return array
	 */
	public function getPermissionsByUserId($userId)
	{
		if (!isset($this->_permissionsByUserId[$userId]))
		{
			$groupPermissions = $this->getGroupPermissionsByUserId($userId);

			$userPermissions = blx()->db->createCommand()
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
	 * @param int $userId
	 * @param string $checkPermission
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
	 * @param int $userId
	 * @param array $permissions
	 * @return bool
	 */
	public function saveUserPermissions($userId, $permissions)
	{
		// Delete any existing user permissions
		blx()->db->createCommand()
			->delete('userpermissions_users', array('userId' => $userId));

		// Filter out any orphaned permissions
		$permissions = $this->_filterOrphanedPermissions($permissions);

		if ($permissions)
		{
			$userPermissionVals = array();

			foreach ($permissions as $permissionName)
			{
				$permissionRecord = $this->_getPermissionRecordByName($permissionName);
				$userPermissionVals[] = array($permissionRecord->id, $userId);
			}

			// Add the new user permissions
			blx()->db->createCommand()
				->insertAll('userpermissions_users', array('permissionId', 'userId'), $userPermissionVals);
		}

		return true;
	}

	/**
	 * Returns the entry permissions for a given section.
	 *
	 * @access private
	 * @param int|null $sectionId
	 * @return array
	 */
	private function _getEntryPermissions($sectionId = null)
	{
		$suffix = $sectionId ? 'InSection'.$sectionId : '';

		return array(
			"editEntries{$suffix}" => array(
				'label' => Blocks::t('Edit entries'),
				'nested' => array(
					"createEntries{$suffix}" => array(
						'label' => Blocks::t('Create entries'),
					),
					"editPeerEntries{$suffix}" => array(
						'label' => Blocks::t('Edit other authors’ entries'),
						'nested' => (Blocks::hasPackage(BlocksPackage::PublishPro)
							? array(
								"editPeerEntryDrafts{$suffix}" => array(
									'label' => Blocks::t('Edit other authors’ drafts'),
									'nested' => array(
										"publishPeerEntryDrafts{$suffix}" => array(
											'label' => Blocks::t('Publish other authors’ drafts')
										),
									)
								),
								"deletePeerEntries{$suffix}" => array(
									'label' => Blocks::t('Delete other authors’ entries')
								),
							) : array()
						)
					),
					"publishEntries{$suffix}" => array(
						'label' => Blocks::t('Publish entries live')
					),
				)
			)
		);
	}

	/**
	 * Returns the singleton permissions.
	 *
	 * @access private
	 * @param array $singletons
	 * @return array
	 */
	private function _getSingletonPermissions($singletons)
	{
		$permissions = array();

		foreach ($singletons as $singleton)
		{
			$permissions["editSingleton{$singleton->id}"] = array(
				'label' => Blocks::t('Edit “{title}”', array('title' => $singleton->title))
			);
		}

		return $permissions;
	}

	/**
	 * Returns the global permissions.
	 *
	 * @access private
	 * @param array $globals
	 * @return array
	 */
	private function _getGlobalPermissions($globals)
	{
		$permissions = array();

		foreach ($globals as $global)
		{
			$permissions["editGlobal{$global->id}"] = array(
				'label' => Blocks::t('Edit “{name}”', array('name' => Blocks::t($global->name)))
			);
		}

		return $permissions;
	}

	/**
	 * Filters out any orphaned permissions.
	 *
	 * @access private
	 * @param array $postedPermissions
	 * @return array $filteredPermissions
	 */
	private function _filterOrphanedPermissions($postedPermissions)
	{
		$filteredPermissions = array();

		if ($postedPermissions)
		{
			foreach ($this->getAllPermissions() as $categoryPermissions)
			{
				$this->_findSelectedPermissions($categoryPermissions, $postedPermissions, $filteredPermissions);
			}
		}

		return $filteredPermissions;
	}

	/**
	 * Iterates through a group of permissions, returning the ones that were selected.
	 *
	 * @access private
	 * @param array $permissionsGroup
	 * @param array $postedPermissions
	 * @param array &$filteredPermissions
	 */
	private function _findSelectedPermissions($permissionsGroup, $postedPermissions, &$filteredPermissions)
	{
		foreach ($permissionsGroup as $name => $data)
		{
			if (in_array($name, $postedPermissions))
			{
				$filteredPermissions[] = $name;

				if (!empty($data['nested']))
				{
					$this->_findSelectedPermissions($data['nested'], $postedPermissions, $filteredPermissions);
				}
			}
		}
	}

	/**
	 * Returns a permission record based on its name. If a record doesn't exist, it will be created.
	 *
	 * @access private
	 * @param string $permissionName
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
