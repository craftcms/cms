<?php

/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\services;

use Craft;
use craft\base\UtilityInterface;
use craft\db\Query;
use craft\db\Table;
use craft\elements\User;
use craft\errors\WrongEditionException;
use craft\events\ConfigEvent;
use craft\events\RegisterUserPermissionsEvent;
use craft\helpers\Db;
use craft\helpers\ProjectConfig as ProjectConfigHelper;
use craft\models\CategoryGroup;
use craft\models\Section;
use craft\models\UserGroup;
use craft\records\UserPermission as UserPermissionRecord;
use craft\utilities\ProjectConfig as ProjectConfigUtility;
use yii\base\Component;
use yii\db\Exception;

/**
 * User Permissions service.
 * An instance of the User Permissions service is globally accessible in Craft via [[\craft\base\ApplicationTrait::getUserPermissions()|`Craft::$app->userPermissions`]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class UserPermissions extends Component
{
    /**
     * @event RegisterUserPermissionsEvent The event that is triggered when registering user permissions.
     */
    const EVENT_REGISTER_PERMISSIONS = 'registerPermissions';

    /**
     * @var
     */
    private $_permissionsByGroupId;

    /**
     * @var
     */
    private $_permissionsByUserId;

    /**
     * Returns all of the known permissions, sorted by category.
     *
     * @return array
     */
    public function getAllPermissions(): array
    {
        $permissions = [];

        // General
        // ---------------------------------------------------------------------

        $general = [
            'accessSiteWhenSystemIsOff' => [
                'label' => Craft::t('app', 'Access the site when the system is off')
            ],
            'accessCp' => [
                'label' => Craft::t('app', 'Access the control panel'),
                'nested' => [
                    'accessCpWhenSystemIsOff' => [
                        'label' => Craft::t('app', 'Access the control panel when the system is offline')
                    ],
                    'performUpdates' => [
                        'label' => Craft::t('app', 'Perform Craft CMS and plugin updates')
                    ],
                ]
            ],
            'customizeSources' => [
                'label' => Craft::t('app', 'Customize element sources'),
            ],
        ];

        foreach (Craft::$app->getPlugins()->getAllPlugins() as $plugin) {
            if ($plugin->hasCpSection) {
                $general['accessCp']['nested']['accessPlugin-' . $plugin->id] = [
                    'label' => Craft::t('app', 'Access {plugin}', ['plugin' => $plugin->name])
                ];
            }
        }

        $permissions[Craft::t('app', 'General')] = $general;

        // Users
        // ---------------------------------------------------------------------

        if (Craft::$app->getEdition() === Craft::Pro) {
            $userPermissions = [
                'editUsers' => [
                    'label' => Craft::t('app', 'Edit users'),
                    'nested' => [
                        'registerUsers' => [
                            'label' => Craft::t('app', 'Register users')
                        ],
                        'moderateUsers' => [
                            'label' => Craft::t('app', 'Moderate users'),
                            'info' => Craft::t('app', 'Includes suspending, unsuspending, and unlocking user accounts.'),
                        ],
                        'assignUserPermissions' => [
                            'label' => Craft::t('app', 'Assign user permissions')
                        ],
                        'assignUserGroups' => [
                            'label' => Craft::t('app', 'Assign user groups')
                        ],
                        'administrateUsers' => [
                            'label' => Craft::t('app', 'Administrate users'),
                            'info' => Craft::t('app', 'Includes activating user accounts, resetting passwords, and changing email addresses.'),
                            'warning' => Craft::t('app', 'Accounts with this permission could use it to escalate their own permissions.'),
                        ],
                        'impersonateUsers' => [
                            'label' => Craft::t('app', 'Impersonate users'),
                        ],
                    ],
                ],
                'deleteUsers' => [
                    'label' => Craft::t('app', 'Delete users')
                ],
            ];

            foreach (Craft::$app->getUserGroups()->getAllGroups() as $userGroup) {
                $userPermissions['editUsers']['nested']['assignUserGroups']['nested']['assignUserGroup:' . $userGroup->uid] = [
                    'label' => Craft::t('app', 'Assign users to “{group}”', [
                        'group' => Craft::t('site', $userGroup->name)
                    ])
                ];
            }

            $permissions[Craft::t('app', 'Users')] = $userPermissions;
        }

        // Sites
        // ---------------------------------------------------------------------

        if (Craft::$app->getIsMultiSite()) {
            $label = Craft::t('app', 'Sites');
            $sites = Craft::$app->getSites()->getAllSites();

            foreach ($sites as $site) {
                $permissions[$label]['editSite:' . $site->uid] = [
                    'label' => Craft::t('app', 'Edit “{title}”',
                        ['title' => Craft::t('site', $site->name)])
                ];
            }
        }

        // Entries
        // ---------------------------------------------------------------------

        $sections = Craft::$app->getSections()->getAllSections();

        foreach ($sections as $section) {
            $label = Craft::t('app', 'Section - {section}',
                ['section' => Craft::t('site', $section->name)]);

            if ($section->type == Section::TYPE_SINGLE) {
                $permissions[$label] = $this->_getSingleEntryPermissions($section);
            } else {
                $permissions[$label] = $this->_getEntryPermissions($section);
            }
        }

        // Global sets
        // ---------------------------------------------------------------------

        $globalSets = Craft::$app->getGlobals()->getAllSets();

        if (!empty($globalSets)) {
            $permissions[Craft::t('app', 'Global Sets')] = $this->_getGlobalSetPermissions($globalSets);
        }

        // Categories
        // ---------------------------------------------------------------------

        $categoryGroups = Craft::$app->getCategories()->getAllGroups();

        if (!empty($categoryGroups)) {
            $permissions[Craft::t('app', 'Categories')] = $this->_getCategoryGroupPermissions($categoryGroups);
        }

        // Volumes
        // ---------------------------------------------------------------------

        $volumes = Craft::$app->getVolumes()->getAllVolumes();

        foreach ($volumes as $volume) {
            $label = Craft::t('app', 'Volume - {volume}', ['volume' => Craft::t('site', $volume->name)]);
            $permissions[$label] = $this->_getVolumePermissions($volume->uid);
        }

        // Utilities
        // ---------------------------------------------------------------------

        $permissions[Craft::t('app', 'Utilities')] = $this->_getUtilityPermissions();

        // Let plugins customize them and add new ones
        // ---------------------------------------------------------------------

        $event = new RegisterUserPermissionsEvent([
            'permissions' => $permissions
        ]);
        $this->trigger(self::EVENT_REGISTER_PERMISSIONS, $event);

        return $event->permissions;
    }

    /**
     * Returns the permissions that the current user is allowed to assign to another user.
     *
     * @param User|null $user The recipient of the permissions. If set, their current permissions will be included as well.
     * @return array
     */
    public function getAssignablePermissions(User $user = null): array
    {
        // If either user is an admin, all permissions are fair game
        if (Craft::$app->getUser()->getIsAdmin() || ($user !== null && $user->admin)) {
            return $this->getAllPermissions();
        }

        $allowedPermissions = [];

        foreach ($this->getAllPermissions() as $category => $permissions) {
            $filteredPermissions = $this->_filterUnassignablePermissions($permissions, $user);

            if (!empty($filteredPermissions)) {
                $allowedPermissions[$category] = $filteredPermissions;
            }
        }

        return $allowedPermissions;
    }

    /**
     * Returns all of a given user group's permissions.
     *
     * @param int $groupId
     * @return array
     */
    public function getPermissionsByGroupId(int $groupId): array
    {
        if (!isset($this->_permissionsByGroupId[$groupId])) {
            $groupPermissions = $this->_createUserPermissionsQuery()
                ->innerJoin(['p_g' => Table::USERPERMISSIONS_USERGROUPS], '[[p_g.permissionId]] = [[p.id]]')
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
     * @return array
     */
    public function getGroupPermissionsByUserId(int $userId): array
    {
        return $this->_createUserPermissionsQuery()
            ->innerJoin(['p_g' => Table::USERPERMISSIONS_USERGROUPS], '[[p_g.permissionId]] = [[p.id]]')
            ->innerJoin(['g_u' => Table::USERGROUPS_USERS], '[[g_u.groupId]] = [[p_g.groupId]]')
            ->where(['g_u.userId' => $userId])
            ->column();
    }

    /**
     * Returns whether a given user group has a given permission.
     *
     * @param int $groupId
     * @param string $checkPermission
     * @return bool
     */
    public function doesGroupHavePermission(int $groupId, string $checkPermission): bool
    {
        $allPermissions = $this->getPermissionsByGroupId($groupId);
        $checkPermission = strtolower($checkPermission);

        return in_array($checkPermission, $allPermissions, true);
    }

    /**
     * Saves new permissions for a user group.
     *
     * @param int $groupId
     * @param array $permissions
     * @return bool
     * @throws WrongEditionException if this is called from Craft Solo edition
     */
    public function saveGroupPermissions(int $groupId, array $permissions): bool
    {
        Craft::$app->requireEdition(Craft::Pro);

        // Lowercase the permissions
        $permissions = array_map('strtolower', $permissions);

        // Filter out any orphaned permissions
        $permissions = $this->_filterOrphanedPermissions($permissions);

        /** @var UserGroup $group */
        $group = Craft::$app->getUserGroups()->getGroupById($groupId);
        $path = UserGroups::CONFIG_USERPGROUPS_KEY . '.' . $group->uid . '.permissions';
        Craft::$app->getProjectConfig()->set($path, $permissions, "Update permissions for user group “{$group->handle}”");

        return true;
    }

    /**
     * Returns all of a given user's permissions.
     *
     * @param int $userId
     * @return array
     */
    public function getPermissionsByUserId(int $userId): array
    {
        if (!isset($this->_permissionsByUserId[$userId])) {
            $groupPermissions = $this->getGroupPermissionsByUserId($userId);

            $userPermissions = $this->_createUserPermissionsQuery()
                ->innerJoin(['p_u' => Table::USERPERMISSIONS_USERS], '[[p_u.permissionId]] = [[p.id]]')
                ->where(['p_u.userId' => $userId])
                ->column();

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
    public function doesUserHavePermission(int $userId, string $checkPermission): bool
    {
        $allPermissions = $this->getPermissionsByUserId($userId);
        $checkPermission = strtolower($checkPermission);

        return in_array($checkPermission, $allPermissions, true);
    }

    /**
     * Saves new permissions for a user.
     *
     * @param int $userId
     * @param array $permissions
     * @return bool
     * @throws WrongEditionException if this is called from Craft Solo edition
     * @throws Exception
     */
    public function saveUserPermissions(int $userId, array $permissions): bool
    {
        Craft::$app->requireEdition(Craft::Pro);

        // Delete any existing user permissions
        Db::delete(Table::USERPERMISSIONS_USERS, [
            'userId' => $userId,
        ]);

        // Lowercase the permissions
        $permissions = array_map('strtolower', $permissions);

        // Filter out any orphaned permissions
        $groupPermissions = $this->getGroupPermissionsByUserId($userId);
        $permissions = $this->_filterOrphanedPermissions($permissions, $groupPermissions);

        if (!empty($permissions)) {
            $userPermissionVals = [];

            foreach ($permissions as $permissionName) {
                $permissionRecord = $this->_getPermissionRecordByName($permissionName);
                $userPermissionVals[] = [$permissionRecord->id, $userId];
            }

            // Add the new user permissions
            Db::batchInsert(Table::USERPERMISSIONS_USERS, ['permissionId', 'userId'], $userPermissionVals);
        }

        // Cache the new permissions
        $this->_permissionsByUserId[$userId] = array_unique(array_merge($groupPermissions, $permissions));

        return true;
    }

    /**
     * Handle any changed group permissions.
     *
     * @param ConfigEvent $event
     */
    public function handleChangedGroupPermissions(ConfigEvent $event)
    {
        // Ensure all user groups are ready to roll
        ProjectConfigHelper::ensureAllUserGroupsProcessed();
        $uid = $event->tokenMatches[0];
        $permissions = $event->newValue;

        /** @var UserGroup $userGroup */
        $userGroup = Craft::$app->getUserGroups()->getGroupByUid($uid);

        // No group - no permissions to change.
        if (!$userGroup) {
            return;
        }

        // Delete any existing group permissions
        Db::delete(Table::USERPERMISSIONS_USERGROUPS, [
            'groupId' => $userGroup->id,
        ]);

        $groupPermissionVals = [];

        if ($permissions) {
            foreach ($permissions as $permissionName) {
                $permissionRecord = $this->_getPermissionRecordByName($permissionName);
                $groupPermissionVals[] = [$permissionRecord->id, $userGroup->id];
            }

            // Add the new group permissions
            Db::batchInsert(Table::USERPERMISSIONS_USERGROUPS, ['permissionId', 'groupId'], $groupPermissionVals);
        }

        // Update caches
        $this->_permissionsByGroupId[$userGroup->id] = $permissions;
    }

    /**
     * Returns the entry permissions for a given Single section.
     *
     * @param Section $section
     * @return array
     */
    private function _getSingleEntryPermissions(Section $section): array
    {
        $suffix = ':' . $section->uid;

        return [
            "editEntries{$suffix}" => [
                'label' => Craft::t('app', 'Edit “{title}”',
                    ['title' => Craft::t('site', $section->name)]),
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
     * @return array
     */
    private function _getEntryPermissions(Section $section): array
    {
        $suffix = ':' . $section->uid;

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
     * @return array
     */
    private function _getGlobalSetPermissions(array $globalSets): array
    {
        $permissions = [];

        foreach ($globalSets as $globalSet) {
            $permissions['editGlobalSet:' . $globalSet->uid] = [
                'label' => Craft::t('app', 'Edit “{title}”',
                    ['title' => Craft::t('site', $globalSet->name)])
            ];
        }

        return $permissions;
    }

    /**
     * Returns the category permissions.
     *
     * @param CategoryGroup[] $groups
     * @return array
     */
    private function _getCategoryGroupPermissions(array $groups): array
    {
        $permissions = [];

        foreach ($groups as $group) {
            $permissions['editCategories:' . $group->uid] = [
                'label' => Craft::t('app', 'Edit “{title}”',
                    ['title' => Craft::t('site', $group->name)])
            ];
        }

        return $permissions;
    }

    /**
     * Returns the array source permissions.
     *
     * @param string $volumeUid
     * @return array
     */
    private function _getVolumePermissions(string $volumeUid): array
    {
        $suffix = ':' . $volumeUid;

        return [
            "viewVolume{$suffix}" => [
                'label' => Craft::t('app', 'View volume'),
                'nested' => [
                    "saveAssetInVolume{$suffix}" => [
                        'label' => Craft::t('app', 'Upload files'),
                    ],
                    "createFoldersInVolume{$suffix}" => [
                        'label' => Craft::t('app', 'Create subfolders'),
                    ],
                    "deleteFilesAndFoldersInVolume{$suffix}" => [
                        'label' => Craft::t('app', 'Remove files and folders'),
                    ],
                    "replaceFilesInVolume{$suffix}" => [
                        'label' => Craft::t('app', 'Replace files'),
                    ],
                    "editImagesInVolume{$suffix}" => [
                        'label' => Craft::t('app', 'Edit images'),
                    ],
                    "viewPeerFilesInVolume{$suffix}" => [
                        'label' => Craft::t('app', 'View files uploaded by other users'),
                        'nested' => [
                            "editPeerFilesInVolume{$suffix}" => [
                                'label' => Craft::t('app', 'Edit files uploaded by other users'),
                            ],
                            "replacePeerFilesInVolume{$suffix}" => [
                                'label' => Craft::t('app', 'Replace files uploaded by other users'),
                                'warning' => Craft::t('app', 'When someone replaces a file, the record of who uploaded the file will be updated as well.'),
                            ],
                            "deletePeerFilesInVolume{$suffix}" => [
                                'label' => Craft::t('app', 'Remove files uploaded by other users'),
                            ],
                            "editPeerImagesInVolume{$suffix}" => [
                                'label' => Craft::t('app', 'Edit images uploaded by other users'),
                            ],
                        ]
                    ]
                ]
            ]
        ];
    }

    /**
     * Returns the permissions for the utilities.
     *
     * @return array
     */
    private function _getUtilityPermissions(): array
    {
        $permissions = [];

        foreach (Craft::$app->getUtilities()->getAllUtilityTypes() as $class) {
            /** @var UtilityInterface $class */
            // Admins only
            if (ProjectConfigUtility::id() === $class::id()) {
                continue;
            }

            $permissions['utility:' . $class::id()] = [
                'label' => $class::displayName()
            ];
        }

        return $permissions;
    }

    /**
     * Filters out any permissions that aren't assignable by the current user.
     *
     * @param array $permissions The original permissions
     * @param User|null $user The recipient of the permissions. If set, their current permissions will be included as well.
     * @return array The filtered permissions
     */
    private function _filterUnassignablePermissions(array $permissions, User $user = null): array
    {
        $currentUser = Craft::$app->getUser()->getIdentity();
        if (!$currentUser && !$user) {
            return [];
        }

        $assignablePermissions = [];

        foreach ($permissions as $name => $data) {
            if (($currentUser !== null && $currentUser->can($name)) || ($user !== null && $user->can($name))) {
                if (isset($data['nested'])) {
                    $data['nested'] = $this->_filterUnassignablePermissions($data['nested'], $user);
                }

                $assignablePermissions[$name] = $data;
            }
        }

        return $assignablePermissions;
    }

    /**
     * Filters out any orphaned permissions.
     *
     * @param array $postedPermissions The posted permissions.
     * @param array $groupPermissions Permissions the user is already assigned
     * to via their group, if we're saving a user's permissions.
     * @return array The permissions we'll actually let them save.
     */
    private function _filterOrphanedPermissions(array $postedPermissions, array $groupPermissions = []): array
    {
        $filteredPermissions = [];

        if (!empty($postedPermissions)) {
            foreach ($this->getAllPermissions() as $categoryPermissions) {
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
     * @return bool Whether any permissions were added to $filteredPermissions
     */
    private function _findSelectedPermissions(array $permissionsGroup, array $postedPermissions, array $groupPermissions, array &$filteredPermissions): bool
    {
        $hasAssignedPermissions = false;

        foreach ($permissionsGroup as $name => $data) {
            $name = strtolower($name);
            // Should the user have this permission (either directly or via their group)?
            if (($inPostedPermissions = in_array($name, $postedPermissions, true)) || in_array($name, $groupPermissions, true)) {
                // First assign any nested permissions
                if (!empty($data['nested'])) {
                    $hasAssignedNestedPermissions = $this->_findSelectedPermissions($data['nested'], $postedPermissions, $groupPermissions, $filteredPermissions);
                } else {
                    $hasAssignedNestedPermissions = false;
                }

                // Were they assigned this permission (or any of its nested permissions) directly?
                if ($inPostedPermissions || $hasAssignedNestedPermissions) {
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
     * @return UserPermissionRecord
     */
    private function _getPermissionRecordByName(string $permissionName): UserPermissionRecord
    {
        // Permission names are always stored in lowercase
        $permissionName = strtolower($permissionName);

        $permissionRecord = UserPermissionRecord::findOne(['name' => $permissionName]);

        if (!$permissionRecord) {
            $permissionRecord = new UserPermissionRecord();
            $permissionRecord->name = $permissionName;
            $permissionRecord->save();
        }

        return $permissionRecord;
    }

    /**
     * @return Query
     */
    private function _createUserPermissionsQuery(): Query
    {
        return (new Query())
            ->select(['p.name'])
            ->from(['p' => Table::USERPERMISSIONS]);
    }
}
