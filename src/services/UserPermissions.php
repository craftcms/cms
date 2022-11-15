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
use craft\elements\Asset;
use craft\elements\Category;
use craft\elements\Entry;
use craft\elements\User;
use craft\errors\WrongEditionException;
use craft\events\ConfigEvent;
use craft\events\RegisterUserPermissionsEvent;
use craft\events\UserGroupPermissionsEvent;
use craft\events\UserPermissionsEvent;
use craft\helpers\Db;
use craft\helpers\ProjectConfig as ProjectConfigHelper;
use craft\models\Section;
use craft\models\UserGroup;
use craft\records\UserPermission as UserPermissionRecord;
use craft\utilities\ProjectConfig as ProjectConfigUtility;
use yii\base\Component;
use yii\db\Exception;

/**
 * User Permissions service.
 *
 * An instance of the service is available via [[\craft\base\ApplicationTrait::getUserPermissions()|`Craft::$app->userPermissions`]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class UserPermissions extends Component
{
    /**
     * @event RegisterUserPermissionsEvent The event that is triggered when registering user permissions.
     */
    public const EVENT_REGISTER_PERMISSIONS = 'registerPermissions';

    /**
     * @event UserPermissionsEvent The event triggered before saving user permissions.
     * @since 4.3.0
     */
    public const EVENT_AFTER_SAVE_USER_PERMISSIONS = 'afterSaveUserPermissions';

    /**
     * @event UserGroupPermissionsEvent The event triggered before saving group permissions.
     * @since 4.3.0
     */
    public const EVENT_AFTER_SAVE_GROUP_PERMISSIONS = 'afterSaveGroupPermissions';

    /**
     * @var string[][]
     */
    private array $_permissionsByGroupId = [];

    /**
     * @var string[][]
     */
    private array $_permissionsByUserId = [];

    /**
     * Returns all of the known permissions, divided into groups.
     *
     * Each group will have two keys:
     *
     * - `heading` – The human-facing heading text for the group
     * - `permissions` – An array of permissions for the group
     *
     * Each item of the `permissions` array will have a key set to the permission name (e.g. `accessCp`), and
     * a value set to an array with the following keys:
     *
     * - `label` – The human-facing permission label
     * - `info` _(optional)_ – Informational text about the permission
     * - `warning` _(optional)_ – Warning text about the permission
     * - `nested` _(optional)_ – An array of nested permissions, which can only be assigned if the parent
     *   permission is assigned.
     *
     * @return array
     */
    public function getAllPermissions(): array
    {
        $permissions = [];

        $this->_generalPermissions($permissions);
        $this->_userPermissions($permissions);
        $this->_sitePermissions($permissions);
        $this->_entryPermissions($permissions);
        $this->_globalSetPermissions($permissions);
        $this->_categoryPermissions($permissions);
        $this->_volumePermissions($permissions);
        $this->_utilityPermissions($permissions);

        // Let plugins customize them and add new ones
        // ---------------------------------------------------------------------

        $event = new RegisterUserPermissionsEvent([
            'permissions' => $permissions,
        ]);
        $this->trigger(self::EVENT_REGISTER_PERMISSIONS, $event);

        return $event->permissions;
    }

    /**
     * Returns the permissions that the current user is allowed to assign to another user.
     *
     * See [[getAllPermissions()]] for an explanation of what will be returned.
     *
     * @param User|null $user The recipient of the permissions. If set, their current permissions will be included as well.
     * @return array
     */
    public function getAssignablePermissions(?User $user = null): array
    {
        // If either user is an admin, all permissions are fair game
        if (Craft::$app->getUser()->getIsAdmin() || ($user !== null && $user->admin)) {
            return $this->getAllPermissions();
        }

        $allowedPermissions = [];

        foreach ($this->getAllPermissions() as $group) {
            $filteredPermissions = $this->_filterUnassignablePermissions($group['permissions'], $user);

            if (!empty($filteredPermissions)) {
                $allowedPermissions[] = [
                    'heading' => $group['heading'],
                    'permissions' => $filteredPermissions,
                ];
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
            /** @var string[] $groupPermissions */
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
     * @return string[]
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

        // Sort ascending
        sort($permissions);

        /** @var UserGroup $group */
        $group = Craft::$app->getUserGroups()->getGroupById($groupId);
        $path = ProjectConfig::PATH_USER_GROUPS . '.' . $group->uid . '.permissions';
        Craft::$app->getProjectConfig()->set($path, $permissions, "Update permissions for user group “{$group->handle}”");

        // Trigger an afterSaveGroupPermissions event
        if ($this->hasEventHandlers(self::EVENT_AFTER_SAVE_GROUP_PERMISSIONS)) {
            $this->trigger(self::EVENT_AFTER_SAVE_GROUP_PERMISSIONS, new UserGroupPermissionsEvent([
                'groupId' => $groupId,
                'permissions' => $permissions,
            ]));
        }

        return true;
    }

    /**
     * Returns all of a given user’s permissions.
     *
     * @param int $userId
     * @return array
     */
    public function getPermissionsByUserId(int $userId): array
    {
        if (!isset($this->_permissionsByUserId[$userId])) {
            $groupPermissions = $this->getGroupPermissionsByUserId($userId);

            /** @var string[] $userPermissions */
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

        // Trigger an afterSaveUserPermissions event
        if ($this->hasEventHandlers(self::EVENT_AFTER_SAVE_USER_PERMISSIONS)) {
            $this->trigger(self::EVENT_AFTER_SAVE_USER_PERMISSIONS, new UserPermissionsEvent([
                'userId' => $userId,
                'permissions' => $permissions,
            ]));
        }

        return true;
    }

    /**
     * Handle any changed group permissions.
     *
     * @param ConfigEvent $event
     */
    public function handleChangedGroupPermissions(ConfigEvent $event): void
    {
        // Ensure all user groups are ready to roll
        ProjectConfigHelper::ensureAllUserGroupsProcessed();
        $uid = $event->tokenMatches[0];
        $permissions = $event->newValue;
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

    private function _generalPermissions(array &$permissions): void
    {
        $pluginPermissions = [];

        foreach (Craft::$app->getPlugins()->getAllPlugins() as $plugin) {
            if ($plugin->hasCpSection) {
                $pluginPermissions["accessPlugin-$plugin->id"] = [
                    'label' => Craft::t('app', 'Access {plugin}', ['plugin' => $plugin->name]),
                ];
            }
        }

        $permissions[] = [
            'heading' => Craft::t('app', 'General'),
            'permissions' => [
                'accessSiteWhenSystemIsOff' => [
                    'label' => Craft::t('app', 'Access the site when the system is off'),
                ],
                'accessCp' => [
                    'label' => Craft::t('app', 'Access the control panel'),
                    'nested' => array_merge([
                        'accessCpWhenSystemIsOff' => [
                            'label' => Craft::t('app', 'Access the control panel when the system is offline'),
                        ],
                        'performUpdates' => [
                            'label' => Craft::t('app', 'Perform Craft CMS and plugin updates'),
                        ],
                    ], $pluginPermissions),
                ],
            ],
        ];
    }

    private function _userPermissions(array &$permissions): void
    {
        if (Craft::$app->getEdition() !== Craft::Pro) {
            return;
        }

        $assignGroupPermissions = [];

        foreach (Craft::$app->getUserGroups()->getAllGroups() as $group) {
            $assignGroupPermissions["assignUserGroup:$group->uid"] = [
                'label' => Craft::t('app', 'Assign users to “{group}”', [
                    'group' => Craft::t('site', $group->name),
                ]),
            ];
        }

        $permissions[] = [
            'heading' => Craft::t('app', 'Users'),
            'permissions' => [
                'editUsers' => [
                    'label' => Craft::t('app', 'Edit users'),
                    'nested' => array_merge(
                        [
                            'registerUsers' => [
                                'label' => Craft::t('app', 'Register users'),
                            ],
                            'moderateUsers' => [
                                'label' => Craft::t('app', 'Moderate users'),
                                'info' => Craft::t('app', 'Includes suspending, unsuspending, and unlocking user accounts.'),
                            ],
                            'administrateUsers' => [
                                'label' => Craft::t('app', 'Administrate users'),
                                'info' => Craft::t('app', 'Includes activating/deactivating user accounts, resetting passwords, and changing email addresses.'),
                                'warning' => Craft::t('app', 'Accounts with this permission could use it to escalate their own permissions.'),
                            ],
                            'impersonateUsers' => [
                                'label' => Craft::t('app', 'Impersonate users'),
                            ],
                            'assignUserPermissions' => [
                                'label' => Craft::t('app', 'Assign user permissions'),
                            ],
                        ],
                        $assignGroupPermissions
                    ),
                ],
                'deleteUsers' => [
                    'label' => Craft::t('app', 'Delete users'),
                ],
            ],
        ];
    }

    private function _sitePermissions(array &$permissions): void
    {
        if (!Craft::$app->getIsMultiSite()) {
            return;
        }

        $sitePermissions = [];

        foreach (Craft::$app->getSites()->getAllSites(true) as $site) {
            $sitePermissions["editSite:$site->uid"] = [
                'label' => Craft::t('app', 'Edit “{title}”', [
                    'title' => Craft::t('site', $site->getName()),
                ]),
            ];
        }

        $permissions[] = [
            'heading' => Craft::t('app', 'Sites'),
            'permissions' => $sitePermissions,
        ];
    }

    private function _entryPermissions(array &$permissions): void
    {
        $sections = Craft::$app->getSections()->getAllSections();

        if (!$sections) {
            return;
        }

        $type = Entry::lowerDisplayName();
        $pluralType = Entry::pluralLowerDisplayName();

        foreach ($sections as $section) {
            if ($section->type == Section::TYPE_SINGLE) {
                $sectionPermissions = [
                    "viewEntries:$section->uid" => [
                        'label' => Craft::t('app', 'View {type}', ['type' => $type]),
                        'nested' => [
                            "saveEntries:$section->uid" => [
                                'label' => Craft::t('app', 'Save {type}', ['type' => $type]),
                            ],
                            "viewPeerEntryDrafts:$section->uid" => [
                                'label' => Craft::t('app', 'View other users’ {type}', [
                                    'type' => Craft::t('app', 'drafts'),
                                ]),
                                'nested' => [
                                    "savePeerEntryDrafts:$section->uid" => [
                                        'label' => Craft::t('app', 'Save other users’ {type}', [
                                            'type' => Craft::t('app', 'drafts'),
                                        ]),
                                    ],
                                    "deletePeerEntryDrafts:$section->uid" => [
                                        'label' => Craft::t('app', 'Delete other users’ {type}', [
                                            'type' => Craft::t('app', 'drafts'),
                                        ]),
                                    ],
                                ],
                            ],
                        ],
                    ],
                ];
            } else {
                $sectionPermissions = [
                    "viewEntries:$section->uid" => [
                        'label' => Craft::t('app', 'View {type}', ['type' => $pluralType]),
                        'nested' => [
                            "createEntries:$section->uid" => [
                                'label' => Craft::t('app', 'Create {type}', ['type' => $pluralType]),
                            ],
                            "saveEntries:$section->uid" => [
                                'label' => Craft::t('app', 'Save {type}', ['type' => $pluralType]),
                            ],
                            "deleteEntries:$section->uid" => [
                                'label' => Craft::t('app', 'Delete {type}', ['type' => $pluralType]),
                            ],
                            "viewPeerEntries:$section->uid" => [
                                'label' => Craft::t('app', 'View other users’ {type}', ['type' => $pluralType]),
                                'nested' => [
                                    "savePeerEntries:$section->uid" => [
                                        'label' => Craft::t('app', 'Save other users’ {type}', ['type' => $pluralType]),
                                    ],
                                    "deletePeerEntries:$section->uid" => [
                                        'label' => Craft::t('app', 'Delete other users’ {type}', ['type' => $pluralType]),
                                    ],
                                ],
                            ],
                            "viewPeerEntryDrafts:$section->uid" => [
                                'label' => Craft::t('app', 'View other users’ {type}', [
                                    'type' => Craft::t('app', 'drafts'),
                                ]),
                                'nested' => [
                                    "savePeerEntryDrafts:$section->uid" => [
                                        'label' => Craft::t('app', 'Save other users’ {type}', [
                                            'type' => Craft::t('app', 'drafts'),
                                        ]),
                                    ],
                                    "deletePeerEntryDrafts:$section->uid" => [
                                        'label' => Craft::t('app', 'Delete other users’ {type}', [
                                            'type' => Craft::t('app', 'drafts'),
                                        ]),
                                    ],
                                ],
                            ],
                        ],
                    ],
                ];
            }

            $permissions[] = [
                'heading' => Craft::t('app', 'Section - {section}', [
                    'section' => Craft::t('site', $section->name),
                ]),
                'permissions' => $sectionPermissions,
            ];
        }
    }

    private function _globalSetPermissions(array &$permissions): void
    {
        $globalSets = Craft::$app->getGlobals()->getAllSets();

        if (!$globalSets) {
            return;
        }

        $globalSetPermissions = [];

        foreach ($globalSets as $globalSet) {
            $globalSetPermissions["editGlobalSet:$globalSet->uid"] = [
                'label' => Craft::t('app', 'Edit “{title}”', [
                    'title' => Craft::t('site', $globalSet->name),
                ]),
            ];
        }

        $permissions[] = [
            'heading' => Craft::t('app', 'Global Sets'),
            'permissions' => $globalSetPermissions,
        ];
    }

    private function _categoryPermissions(array &$permissions): void
    {
        $categoryGroups = Craft::$app->getCategories()->getAllGroups();

        if (!$categoryGroups) {
            return;
        }

        $type = Category::pluralLowerDisplayName();

        foreach ($categoryGroups as $group) {
            $permissions[] = [
                'heading' => Craft::t('app', 'Category Group - {name}', [
                    'name' => Craft::t('site', $group->name),
                ]),
                'permissions' => [
                    "viewCategories:$group->uid" => [
                        'label' => Craft::t('app', 'View {type}', ['type' => $type]),
                        'nested' => [
                            "saveCategories:$group->uid" => [
                                'label' => Craft::t('app', 'Save {type}', ['type' => $type]),
                            ],
                            "deleteCategories:$group->uid" => [
                                'label' => Craft::t('app', 'Delete {type}', ['type' => $type]),
                            ],
                            "viewPeerCategoryDrafts:$group->uid" => [
                                'label' => Craft::t('app', 'View other users’ {type}', [
                                    'type' => Craft::t('app', 'drafts'),
                                ]),
                                'nested' => [
                                    "savePeerCategoryDrafts:$group->uid" => [
                                        'label' => Craft::t('app', 'Save other users’ {type}', [
                                            'type' => Craft::t('app', 'drafts'),
                                        ]),
                                    ],
                                    "deletePeerCategoryDrafts:$group->uid" => [
                                        'label' => Craft::t('app', 'Delete other users’ {type}', [
                                            'type' => Craft::t('app', 'drafts'),
                                        ]),
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ];
        }
    }

    private function _volumePermissions(array &$permissions): void
    {
        $volumes = Craft::$app->getVolumes()->getAllVolumes();

        if (!$volumes) {
            return;
        }

        $type = Asset::pluralLowerDisplayName();

        foreach ($volumes as $volume) {
            $permissions[] = [
                'heading' => Craft::t('app', 'Volume - {volume}', [
                    'volume' => Craft::t('site', $volume->name),
                ]),
                'permissions' => [
                    "viewAssets:$volume->uid" => [
                        'label' => Craft::t('app', 'View {type}', ['type' => $type]),
                        'nested' => [
                            "saveAssets:$volume->uid" => [
                                'label' => Craft::t('app', 'Save {type}', ['type' => $type]),
                            ],
                            "deleteAssets:$volume->uid" => [
                                'label' => Craft::t('app', 'Delete {type}', ['type' => $type]),
                            ],
                            "replaceFiles:$volume->uid" => [
                                'label' => Craft::t('app', 'Replace files'),
                            ],
                            "editImages:$volume->uid" => [
                                'label' => Craft::t('app', 'Edit images'),
                            ],
                            "viewPeerAssets:$volume->uid" => [
                                'label' => Craft::t('app', 'View assets uploaded by other users'),
                                'nested' => [
                                    "savePeerAssets:$volume->uid" => [
                                        'label' => Craft::t('app', 'Save assets uploaded by other users'),
                                    ],
                                    "replacePeerFiles:$volume->uid" => [
                                        'label' => Craft::t('app', 'Replace files uploaded by other users'),
                                        'warning' => Craft::t('app', 'When someone replaces a file, the record of who uploaded the file will be updated as well.'),
                                    ],
                                    "deletePeerAssets:$volume->uid" => [
                                        'label' => Craft::t('app', 'Remove files uploaded by other users'),
                                    ],
                                    "editPeerImages:$volume->uid" => [
                                        'label' => Craft::t('app', 'Edit images uploaded by other users'),
                                    ],
                                ],
                            ],
                            "createFolders:$volume->uid" => [
                                'label' => Craft::t('app', 'Create subfolders'),
                            ],
                        ],
                    ],
                ],
            ];
        }
    }

    private function _utilityPermissions(array &$permissions): void
    {
        $utilityPermissions = [];

        foreach (Craft::$app->getUtilities()->getAllUtilityTypes() as $class) {
            /** @var UtilityInterface $class */
            // Admins only
            if (ProjectConfigUtility::id() === $class::id()) {
                continue;
            }

            $utilityPermissions[sprintf('utility:%s', $class::id())] = [
                'label' => $class::displayName(),
            ];
        }

        $permissions[] = [
            'heading' => Craft::t('app', 'Utilities'),
            'permissions' => $utilityPermissions,
        ];
    }

    /**
     * Filters out any permissions that aren't assignable by the current user.
     *
     * @param array $permissions The original permissions
     * @param User|null $user The recipient of the permissions. If set, their current permissions will be included as well.
     * @return array The filtered permissions
     */
    private function _filterUnassignablePermissions(array $permissions, ?User $user = null): array
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
     * to via their group, if we’re saving a user’s permissions.
     * @return array The permissions we'll actually let them save.
     */
    private function _filterOrphanedPermissions(array $postedPermissions, array $groupPermissions = []): array
    {
        $filteredPermissions = [];

        if (!empty($postedPermissions)) {
            foreach ($this->getAllPermissions() as $group) {
                $this->_findSelectedPermissions($group['permissions'], $postedPermissions, $groupPermissions, $filteredPermissions);
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
     * @param array $filteredPermissions
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
