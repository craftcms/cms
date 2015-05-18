<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\services;

use Craft;
use craft\app\db\Query;
use craft\app\models\Section;
use craft\app\records\UserPermission as UserPermissionRecord;
use yii\base\Component;

Craft::$app->requireEdition(Craft::Pro);

/**
 * Class UserPermissions service.
 *
 * An instance of the UserPermissions service is globally accessible in Craft via [[Application::userPermissions `Craft::$app->getUserPermissions()`]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class UserPermissions extends Component
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

		$general = [
			'accessSiteWhenSystemIsOff' => [
				'label' => Craft::t('app', 'Access the site when the system is off')
			],
			'accessCp' => [
				'label' => Craft::t('app', 'Access the CP'),
				'nested' => [
					'accessCpWhenSystemIsOff' => [
						'label' => Craft::t('app', 'Access the CP when the system is off')
					],
					'performUpdates' => [
						'label' => Craft::t('app', 'Perform Craft and plugin updates')
					],
				]
			],
		];

		foreach (Craft::$app->getPlugins()->getAllPlugins() as $plugin)
		{
			if ($plugin::hasCpSection())
			{
				$general['accessCp']['nested']['accessPlugin-'.$plugin->getHandle()] = [
					'label' => Craft::t('app', 'Access {plugin}', ['plugin' => $plugin->name])
				];
			}
		}

		$permissions[Craft::t('app', 'General')] = $general;

		// Users
		// ---------------------------------------------------------------------

		$permissions[Craft::t('app', 'Users')] = [
			'editUsers' => [
				'label' => Craft::t('app', 'Edit users'),
				'nested' => [
					'registerUsers' => [
						'label' => Craft::t('app', 'Register users')
					],
					'assignUserPermissions' => [
						'label' => Craft::t('app', 'Assign user groups and permissions')
					],
					'administrateUsers' => [
						'label' => Craft::t('app', 'Administrate users'),
						'nested' => [
							'changeUserEmails' => [
								'label' => Craft::t('app', 'Change users’ emails')
							]
						]
					]
				],
			],
			'deleteUsers' => [
				'label' => Craft::t('app', 'Delete users')
			],
		];

		// Locales
		// ---------------------------------------------------------------------

		if (Craft::$app->isLocalized())
		{
			$label = Craft::t('app', 'Locales');
			$locales = Craft::$app->getI18n()->getSiteLocales();

			foreach ($locales as $locale)
			{
				$permissions[$label]['editLocale:'.$locale->id] = [
					'label' => $locale->getDisplayName(Craft::$app->language)
				];
			}
		}

		// Entries
		// ---------------------------------------------------------------------

		$sections = Craft::$app->getSections()->getAllSections();

		foreach ($sections as $section)
		{
			$label = Craft::t('app', 'Section - {section}', ['section' => Craft::t('app', 'app', $section->name)]);

			if ($section->type == Section::TYPE_SINGLE)
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

		$globalSets = Craft::$app->getGlobals()->getAllSets();

		if ($globalSets)
		{
			$permissions[Craft::t('app', 'Global Sets')] = $this->_getGlobalSetPermissions($globalSets);
		}

		// Categories
		// ---------------------------------------------------------------------

		$categoryGroups = Craft::$app->getCategories()->getAllGroups();

		if ($categoryGroups)
		{
			$permissions[Craft::t('app', 'Categories')] = $this->_getCategoryGroupPermissions($categoryGroups);
		}

		// Asset sources
		// ---------------------------------------------------------------------

		$volumes = Craft::$app->getVolumes()->getAllVolumes();

		foreach ($volumes as $volume)
		{
			$label = Craft::t('app', 'Volume - {volume}', ['volume' => Craft::t('app', $volume->name)]);
			$permissions[$label] = $this->_getVolumePermissions($volume->id);
		}

		// Plugins
		// ---------------------------------------------------------------------

		foreach (Craft::$app->getPlugins()->call('registerUserPermissions') as $pluginHandle => $pluginPermissions)
		{
			$plugin = Craft::$app->getPlugins()->getPlugin($pluginHandle);
			$permissions[$plugin->name] = $pluginPermissions;
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
			$groupPermissions = (new Query())
				->select('p.name')
				->from('{{%userpermissions}} p')
				->innerJoin('{{%userpermissions_usergroups}} p_g', 'p_g.permissionId = p.id')
				->where(['p_g.groupId' => $groupId])
				->column();

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
		return (new Query())
			->select('p.name')
			->from('{{%userpermissions}} p')
			->innerJoin('{{%userpermissions_usergroups}} p_g', 'p_g.permissionId = p.id')
			->innerJoin('{{%usergroups_users}} g_u', 'g_u.groupId = p_g.groupId')
			->where(['g_u.userId' => $userId])
			->column();
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
		Craft::$app->getDb()->createCommand()->delete('{{%userpermissions_usergroups}}', ['groupId' => $groupId])->execute();

		$permissions = $this->_filterOrphanedPermissions($permissions);

		if ($permissions)
		{
			$groupPermissionVals = [];

			foreach ($permissions as $permissionName)
			{
				$permissionRecord = $this->_getPermissionRecordByName($permissionName);
				$groupPermissionVals[] = [$permissionRecord->id, $groupId];
			}

			// Add the new group permissions
			Craft::$app->getDb()->createCommand()->batchInsert(
				'userpermissions_usergroups',
				['permissionId', 'groupId'],
				$groupPermissionVals
			)->execute();
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

			$userPermissions = (new Query())
				->select('p.name')
				->from('{{%userpermissions}} p')
				->innerJoin('{{%userpermissions_users}} p_u', 'p_u.permissionId = p.id')
				->where(['p_u.userId' => $userId])
				->column();

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
		Craft::$app->getDb()->createCommand()->delete('{{%userpermissions_users}}', ['userId' => $userId])->execute();

		// Filter out any orphaned permissions
		$groupPermissions = $this->getGroupPermissionsByUserId($userId);
		$permissions = $this->_filterOrphanedPermissions($permissions, $groupPermissions);

		if ($permissions)
		{
			$userPermissionVals = [];

			foreach ($permissions as $permissionName)
			{
				$permissionRecord = $this->_getPermissionRecordByName($permissionName);
				$userPermissionVals[] = [$permissionRecord->id, $userId];
			}

			// Add the new user permissions
			Craft::$app->getDb()->createCommand()->batchInsert(
				'userpermissions_users',
				['permissionId', 'userId'],
				$userPermissionVals
			)->execute();
		}

		return true;
	}

	// Private Methods
	// =========================================================================

	/**
	 * Returns the entry permissions for a given Single section.
	 *
	 * @param Section $section
	 *
	 * @return array
	 */
	private function _getSingleEntryPermissions($section)
	{
		$suffix = ':'.$section->id;

		return [
			"editEntries{$suffix}" => [
				'label' => Craft::t('app', 'Edit “{title}”', ['title' => Craft::t('app', $section->name)]),
				'nested' => [
					"publishEntries{$suffix}" => [
						'label' => Craft::t('app', 'Publish live changes')
					],
					"editPeerEntryDrafts{$suffix}" => [
						'label' => Craft::t('app', 'Edit other authors’ drafts'),
						'nested' => [
							"publishPeerEntryDrafts{$suffix}" => [
								'label' => Craft::t('app', 'Publish other authors’ drafts')
							],
							"deletePeerEntryDrafts{$suffix}" => [
								'label' => Craft::t('app', 'Delete other authors’ drafts')
							],
						]
					],
				]
			]
		];
	}

	/**
	 * Returns the entry permissions for a given Channel or Structure section.
	 *
	 * @param Section $section
	 *
	 * @return array
	 */
	private function _getEntryPermissions($section)
	{
		$suffix = ':'.$section->id;

		return [
			"editEntries{$suffix}" => [
				'label' => Craft::t('app', 'Edit entries'),
				'nested' => [
					"createEntries{$suffix}" => [
						'label' => Craft::t('app', 'Create entries'),
					],
					"publishEntries{$suffix}" => [
						'label' => Craft::t('app', 'Publish live changes')
					],
					"deleteEntries{$suffix}" => [
						'label' => Craft::t('app', 'Delete entries')
					],
					"editPeerEntries{$suffix}" => [
						'label' => Craft::t('app', 'Edit other authors’ entries'),
						'nested' => [
							"publishPeerEntries{$suffix}" => [
								'label' => Craft::t('app', 'Publish live changes for other authors’ entries')
							],
							"deletePeerEntries{$suffix}" => [
								'label' => Craft::t('app', 'Delete other authors’ entries')
							],
						]
					],
					"editPeerEntryDrafts{$suffix}" => [
						'label' => Craft::t('app', 'Edit other authors’ drafts'),
						'nested' => [
							"publishPeerEntryDrafts{$suffix}" => [
								'label' => Craft::t('app', 'Publish other authors’ drafts')
							],
							"deletePeerEntryDrafts{$suffix}" => [
								'label' => Craft::t('app', 'Delete other authors’ drafts')
							],
						]
					],
				]
			]
		];
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
		$permissions = [];

		foreach ($globalSets as $globalSet)
		{
			$permissions['editGlobalSet:'.$globalSet->id] = [
				'label' => Craft::t('app', 'Edit “{title}”', ['title' => Craft::t('app', $globalSet->name)])
			];
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
		$permissions = [];

		foreach ($groups as $group)
		{
			$permissions['editCategories:'.$group->id] = [
				'label' => Craft::t('app', 'Edit “{title}”', ['title' => Craft::t('app', $group->name)])
			];
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
	private function _getVolumePermissions($sourceId)
	{
		$suffix = ':'.$sourceId;

		return [
			"viewAssetSource{$suffix}" => [
				'label' => Craft::t('app', 'View source'),
				'nested' => [
					"uploadToAssetSource{$suffix}" => [
						'label' => Craft::t('app', 'Upload files'),
					],
					"createSubfoldersInAssetSource{$suffix}" => [
						'label' => Craft::t('app', 'Create subfolders'),
					],
					"removeFromAssetSource{$suffix}" => [
						'label' => Craft::t('app', 'Remove files'),
					]
				]
			]
		];
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
	private function _filterOrphanedPermissions($postedPermissions, $groupPermissions = [])
	{
		$filteredPermissions = [];

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
	 * @return null
	 */
	private function _findSelectedPermissions($permissionsGroup, $postedPermissions, $groupPermissions, &$filteredPermissions)
	{
		foreach ($permissionsGroup as $name => $data)
		{
			// Was this permission in the post data, or do they already have it via their group?
			if (($inPostedPermissions = in_array($name, $postedPermissions)) || in_array(strtolower($name), $groupPermissions))
			{
				// If it was in the post data, give it to them directly now
				if ($inPostedPermissions)
				{
					$filteredPermissions[] = $name;
				}

				if (!empty($data['nested']))
				{
					$this->_findSelectedPermissions($data['nested'], $postedPermissions, $groupPermissions, $filteredPermissions);
				}
			}
		}
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

		$permissionRecord = UserPermissionRecord::findOne([
			'name' => $permissionName
		]);

		if (!$permissionRecord)
		{
			$permissionRecord = new UserPermissionRecord();
			$permissionRecord->name = $permissionName;
			$permissionRecord->save();
		}

		return $permissionRecord;
	}
}
