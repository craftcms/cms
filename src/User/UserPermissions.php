<?php

declare(strict_types=1);

namespace CraftCms\Cms\User;

use Craft;
use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Edition;
use CraftCms\Cms\Edition\Exceptions\WrongEditionException;
use CraftCms\Cms\Element\Enums\PropagationMethod;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Plugin\Plugins;
use CraftCms\Cms\ProjectConfig\Events\ConfigEvent;
use CraftCms\Cms\ProjectConfig\ProjectConfig;
use CraftCms\Cms\ProjectConfig\ProjectConfigHelper;
use CraftCms\Cms\Section\Data\Section;
use CraftCms\Cms\Section\Enums\SectionType;
use CraftCms\Cms\Site\Data\Site;
use CraftCms\Cms\Support\Facades\Sections;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Support\Facades\UserGroups;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\User\Data\Permission;
use CraftCms\Cms\User\Data\PermissionGroup;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\User\Events\RegisterUserPermissions;
use CraftCms\Cms\User\Events\UserGroupPermissionsSaved;
use CraftCms\Cms\User\Events\UserPermissionsSaved;
use CraftCms\Cms\User\Models\UserPermission;
use CraftCms\Cms\Utility\Utilities;
use CraftCms\Cms\Utility\Utilities\ProjectConfig as ProjectConfigUtility;
use CraftCms\Cms\Utility\Utility;
use Illuminate\Container\Attributes\Singleton;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tpetry\QueryExpressions\Language\Alias;

use function CraftCms\Cms\t;

#[Singleton]
final class UserPermissions
{
    /**
     * @var Collection<\CraftCms\Cms\User\Data\PermissionGroup>
     *
     * @see getAllPermissions()
     */
    private Collection $allPermissions;

    /**
     * @var Collection<string>
     *
     * @see validatePermission()
     */
    private Collection $allPermissionNames;

    /**
     * @var Collection<int, Collection<string>>
     */
    private Collection $permissionsByGroupId;

    /**
     * @var Collection<int, Collection<string>>
     */
    private Collection $permissionsByUserId;

    /**
     * Returns all of the known permissions, divided into groups.
     *
     * Each group will have two keys:
     *
     * - `heading` – The human-facing heading text for the group
     * - `permissions` – An array of permissions for the group
     *
     * Each item of the `permissions` array will have a key set to the permission name
     * (e.g. `accessSiteWhenSystemIsOff`), and a value set to an array with the following keys:
     *
     * - `label` – The human-facing permission label
     * - `info` _(optional)_ – Informational text about the permission
     * - `warning` _(optional)_ – Warning text about the permission
     * - `nested` _(optional)_ – An array of nested permissions, which can only be assigned if the parent
     *   permission is assigned.
     *
     * @return Collection<\CraftCms\Cms\User\Data\PermissionGroup>
     */
    public function getAllPermissions(): Collection
    {
        if (isset($this->allPermissions)) {
            return $this->allPermissions;
        }

        $this->allPermissions = new Collection;

        $this->generalPermissions($this->allPermissions);
        $this->userPermissions($this->allPermissions);
        $this->sitePermissions($this->allPermissions);
        $this->entryPermissions($this->allPermissions);
        $this->volumePermissions($this->allPermissions);
        $this->utilityPermissions($this->allPermissions);

        event($event = new RegisterUserPermissions($this->allPermissions));

        return $event->permissions;
    }

    /**
     * Returns the permissions that the current user is allowed to assign to another user.
     *
     * See [[getAllPermissions()]] for an explanation of what will be returned.
     *
     * @param  User|null  $user  The recipient of the permissions. If set, their current permissions will be included as well.
     * @return Collection<PermissionGroup>
     */
    public function getAssignablePermissions(?User $user = null): Collection
    {
        // If either user is an admin, all permissions are fair game
        if (Auth::user()?->isAdmin() || ($user !== null && $user->admin)) {
            return $this->getAllPermissions();
        }

        return $this->getAllPermissions()->map(function (PermissionGroup $group) use ($user) {
            $group->permissions = $this->filterUnassignablePermissions($group->permissions, $user);

            if ($group->permissions->isEmpty()) {
                return null;
            }

            return $group;
        })->filter();
    }

    /**
     * Returns all of a given user group's permissions.
     *
     * @return Collection<string>
     */
    public function getPermissionsByGroupId(int $groupId): Collection
    {
        $this->permissionsByGroupId ??= collect();

        return $this->permissionsByGroupId->getOrPut(
            $groupId,
            fn () => $this->createUserPermissionsQuery()
                ->join(new Alias(Table::USERPERMISSIONS_USERGROUPS, 'p_g'), 'p_g.permissionId', 'p.id')
                ->where('p_g.groupId', $groupId)
                ->pluck('p.name'),
        );
    }

    /**
     * Returns all of the group permissions a given user has.
     *
     * @return Collection<string>
     */
    public function getGroupPermissionsByUserId(int $userId): Collection
    {
        if (Edition::get() === Edition::Team) {
            $group = UserGroups::getTeamGroup();

            return $this->getPermissionsByGroupId($group->id);
        }

        return $this->createUserPermissionsQuery()
            ->join(new Alias(Table::USERPERMISSIONS_USERGROUPS, 'p_g'), 'p_g.permissionId', 'p.id')
            ->join(new Alias(Table::USERGROUPS_USERS, 'g_u'), 'g_u.groupId', 'p_g.groupId')
            ->where('g_u.userId', $userId)
            ->pluck('p.name');
    }

    /**
     * Returns whether a given user group has a given permission.
     */
    public function doesGroupHavePermission(int $groupId, string $checkPermission): bool
    {
        return $this->getPermissionsByGroupId($groupId)->containsStrict(strtolower($checkPermission));
    }

    /**
     * Saves new permissions for a user group.
     *
     * @throws WrongEditionException if this is called from Craft Solo edition
     */
    public function saveGroupPermissions(int $groupId, array $permissions): bool
    {
        Edition::require(Edition::Team);

        // Lowercase permissions
        $permissions = array_map(strtolower(...), $permissions);

        // Filter out any orphaned permissions
        $permissions = $this->filterOrphanedPermissions($permissions);

        // Sort ascending
        sort($permissions);

        $group = UserGroups::getGroupById($groupId);
        $path = ProjectConfig::PATH_USER_GROUPS.'.'.$group->uid.'.permissions';
        app(ProjectConfig::class)->set(
            path: $path,
            value: $permissions,
            message: "Update permissions for user group “{$group->handle}”",
        );

        event(new UserGroupPermissionsSaved($groupId, $permissions));

        return true;
    }

    /**
     * Returns all of a given user’s permissions.
     *
     *
     * @return Collection<string>
     */
    public function getPermissionsByUserId(int $userId): Collection
    {
        $this->permissionsByUserId ??= collect();

        return $this->permissionsByUserId->getOrPut(
            $userId,
            function () use ($userId) {
                $groupPermissions = $this->getGroupPermissionsByUserId($userId);

                if (Edition::get()->value >= Edition::Pro->value) {
                    /** @var string[] $userPermissions */
                    $userPermissions = $this->createUserPermissionsQuery()
                        ->join(new Alias(Table::USERPERMISSIONS_USERS, 'p_u'), 'p_u.permissionId', 'p.id')
                        ->where('p_u.userId', $userId)
                        ->pluck('p.name');
                } else {
                    $userPermissions = [];
                }

                return $groupPermissions->merge($userPermissions)->unique()->values();
            },
        );
    }

    public function validatePermission(string $permission): bool
    {
        if (! isset($this->allPermissionNames)) {
            $this->allPermissionNames = $this->getAllPermissions()->flatMap(fn (PermissionGroup $group) => $this->collectPermissionNames($group->permissions));
        }

        return $this->allPermissionNames->contains(strtolower($permission));
    }

    /**
     * @param  Collection<Permission>  $permissions
     * @return Collection<string>
     */
    private function collectPermissionNames(Collection $permissions): Collection
    {
        return $permissions->flatMap(function (Permission $permission) {
            $names = collect([strtolower($permission->key)]);

            if ($permission->nested->isNotEmpty()) {
                return $names->merge($this->collectPermissionNames($permission->nested));
            }

            return $names;
        });
    }

    /**
     * Returns whether a given user has a given permission.
     */
    public function doesUserHavePermission(int $userId, string $checkPermission): bool
    {
        if (strcasecmp($checkPermission, 'accessCp') === 0 && Edition::get() === Edition::Team) {
            return true;
        }

        return $this->getPermissionsByUserId($userId)->containsStrict(strtolower($checkPermission));
    }

    /**
     * Saves new permissions for a user.
     *
     * @throws WrongEditionException if this is called from Craft Solo edition
     */
    public function saveUserPermissions(int $userId, array $permissions): bool
    {
        Edition::require(Edition::Pro);

        // Delete any existing user permissions
        DB::table(Table::USERPERMISSIONS_USERS)
            ->where('userId', $userId)
            ->delete();

        // Lowercase the permissions
        $permissions = array_map(strtolower(...), $permissions);

        // Filter out any orphaned permissions
        $groupPermissions = $this->getGroupPermissionsByUserId($userId);
        $permissions = $this->filterOrphanedPermissions($permissions, $groupPermissions->all());

        if (! empty($permissions)) {
            $userPermissionVals = [];

            foreach ($permissions as $permissionName) {
                $permissionModel = $this->getPermissionModelByName($permissionName);
                $userPermissionVals[] = [
                    'permissionId' => $permissionModel->id,
                    'userId' => $userId,
                    'dateCreated' => $now = now(),
                    'dateUpdated' => $now,
                    'uid' => Str::uuid(),
                ];
            }

            // Add the new user permissions
            DB::table(Table::USERPERMISSIONS_USERS)->insert($userPermissionVals);
        }

        // Cache the new permissions
        $this->permissionsByUserId ??= collect();
        $this->permissionsByUserId[$userId] = $groupPermissions->merge($permissions)->unique();

        event(new UserPermissionsSaved($userId, $permissions));

        return true;
    }

    /**
     * Handle any changed group permissions.
     */
    public function handleChangedGroupPermissions(ConfigEvent $event): void
    {
        // Ensure all user groups are ready to roll
        ProjectConfigHelper::ensureAllUserGroupsProcessed();
        $uid = $event->tokenMatches[0];
        $permissions = $event->newValue;
        $userGroup = UserGroups::getGroupByUid($uid);

        // No group - no permissions to change.
        if (! $userGroup) {
            return;
        }

        // Delete any existing group permissions
        DB::table(Table::USERPERMISSIONS_USERGROUPS)
            ->where('groupId', $userGroup->id)
            ->delete();

        $groupPermissionVals = [];

        if ($permissions) {
            $now = now();

            foreach ($permissions as $permissionName) {
                $permissionModel = $this->getPermissionModelByName($permissionName);
                $groupPermissionVals[] = [
                    'permissionId' => $permissionModel->id,
                    'groupId' => $userGroup->id,
                    'dateCreated' => $now,
                    'dateUpdated' => $now,
                    'uid' => Str::uuid(),
                ];
            }

            // Add the new group permissions
            DB::table(Table::USERPERMISSIONS_USERGROUPS)->insert($groupPermissionVals);
        }

        // Update caches
        $this->permissionsByGroupId ??= collect();
        $this->permissionsByGroupId->put($userGroup->id, collect($permissions));
        unset($this->permissionsByUserId);
    }

    private function generalPermissions(Collection $permissions): void
    {
        $generalPermissions = collect([
            new Permission(
                key: 'accessSiteWhenSystemIsOff',
                label: t('Access the site when the system is off'),
            ),
        ]);

        $cpPermissions = collect([
            new Permission(
                key: 'accessCpWhenSystemIsOff',
                label: t('Access the control panel when the system is offline'),
            ),
            new Permission(
                key: 'performUpdates',
                label: t('Perform Craft CMS and plugin updates'),
            ),
        ]);

        foreach (app(Plugins::class)->getAllPlugins() as $plugin) {
            if ($plugin->hasCpSection) {
                $cpPermissions->add(new Permission(
                    key: "accessPlugin-$plugin->handle",
                    label: t('Access {plugin}', ['plugin' => $plugin->name]),
                ));
            }
        }

        switch (Edition::get()) {
            case Edition::Team:
                $generalPermissions = $generalPermissions->merge($cpPermissions);
                break;
            case Edition::Pro:
            case Edition::Enterprise:
                $generalPermissions->add(new Permission(
                    key: 'accessCp',
                    label: t('Access the control panel'),
                    warning: t('Includes read-only access to user data and most content, via element selector modals and other means.'),
                    nested: $cpPermissions,
                ));
                break;
        }

        $permissions->add(new PermissionGroup(
            heading: t('General'),
            permissions: $generalPermissions,
        ));
    }

    private function userPermissions(Collection $permissions): void
    {
        $assignGroupPermissions = new Collection;

        if (Edition::get()->value >= Edition::Pro->value) {
            foreach (UserGroups::getAllGroups() as $group) {
                $assignGroupPermissions->add(new Permission(
                    key: "assignUserGroup:$group->uid",
                    label: t('Assign users to “{group}”', [
                        'group' => t($group->name, category: 'site'),
                    ]),
                ));
            }
        }

        $permissions->add(new PermissionGroup(
            heading: t('Users'),
            permissions: collect([
                new Permission(
                    key: 'viewUsers',
                    label: t('View {type}', [
                        'type' => User::pluralLowerDisplayName(),
                    ]),
                    nested: collect([
                        new Permission(
                            key: 'editUsers',
                            label: mb_ucfirst(t('Edit {type}', [
                                'type' => User::pluralLowerDisplayName(),
                            ])),
                            nested: collect([
                                new Permission(key: 'registerUsers', label: t('Register users')),
                                new Permission(
                                    key: 'moderateUsers',
                                    label: t('Moderate users'),
                                    info: t('Includes suspending, unsuspending, and unlocking user accounts.'),
                                ),
                                new Permission(
                                    key: 'administrateUsers',
                                    label: t('Administrate users'),
                                    info: t('Includes activating/deactivating user accounts, resetting passwords, and changing email addresses.'),
                                    warning: Edition::get()->value >= Edition::Pro->value
                                        ? t('Accounts with this permission could use it to escalate their own permissions.')
                                        : null
                                ),
                                new Permission(key: 'impersonateUsers', label: t('Impersonate users')),
                                Edition::get()->value >= Edition::Pro->value
                                    ? new Permission(key: 'assignUserPermissions', label: t('Assign user permissions'))
                                    : null,
                            ])->filter()->merge($assignGroupPermissions),
                        ),
                        new Permission(key: 'deleteUsers', label: t('Delete users')),
                    ])
                ),
            ])
        ));
    }

    private function sitePermissions(Collection $permissions): void
    {
        if (! Sites::isMultiSite()) {
            return;
        }

        $permissions->add(new PermissionGroup(
            heading: t('Sites'),
            permissions: Sites::getAllSites(true)->map(fn (Site $site) => new Permission(
                key: "editSite:$site->uid",
                label: t('Edit “{title}”', [
                    'title' => t($site->getName(), category: 'site'),
                ]),
            ))->values(),
        ));
    }

    private function entryPermissions(Collection $permissions): void
    {
        $sections = Sections::getAllSections();

        if ($sections->isEmpty()) {
            return;
        }

        $entryPermissions = $sections->map(function (Section $section) {
            if ($section->type === SectionType::Single) {
                return [
                    $section,
                    $this->singleSectionPermission($section),
                ];
            }

            return [$section, $this->multiSectionPermission($section)];
        })->map(function (array $result) {
            /** @var array{Section, Permission} $result */
            [$section, $sectionPermission] = $result;

            return new PermissionGroup(
                heading: t('Section - {section}', [
                    'section' => t($section->name, category: 'site'),
                ]),
                permissions: collect([$sectionPermission]),
            );
        });

        $permissions->push(...$entryPermissions);
    }

    private function singleSectionPermission(Section $section): Permission
    {
        if ($section->type !== SectionType::Single) {
            throw new RuntimeException('Section must be of type Single');
        }

        $type = Entry::lowerDisplayName();

        return new Permission(
            key: "viewEntries:$section->uid",
            label: mb_ucfirst(t('View {type}', ['type' => $type])),
            nested: collect([
                new Permission(
                    key: "saveEntries:$section->uid",
                    label: mb_ucfirst(t('Save {type}', ['type' => $type])),
                ),
                new Permission(
                    key: "viewEntries:$section->uid",
                    label: mb_ucfirst(t('View {type}', ['type' => $type])),
                    nested: collect([
                        new Permission(
                            key: "viewPeerEntryDrafts:$section->uid",
                            label: mb_ucfirst(t('View other users’ {type}', [
                                'type' => t('drafts'),
                            ])),
                            nested: collect([
                                new Permission(
                                    key: "savePeerEntryDrafts:$section->uid",
                                    label: mb_ucfirst(t('Save other users’ {type}', [
                                        'type' => t('drafts'),
                                    ])),
                                ),
                                new Permission(
                                    key: "deletePeerEntryDrafts:$section->uid",
                                    label: t('Delete other users’ {type}', [
                                        'type' => t('drafts'),
                                    ]),
                                ),
                            ])
                        ),
                    ]),
                ),
            ])
        );
    }

    private function multiSectionPermission(Section $section): Permission
    {
        if ($section->type === SectionType::Single) {
            throw new RuntimeException('Section must not be of type Single');
        }

        $pluralType = Entry::pluralLowerDisplayName();

        $hasCustomPropagation = (
            $section->propagationMethod === PropagationMethod::Custom &&
            Sites::isMultiSite()
        );

        return new Permission(
            key: "viewEntries:$section->uid",
            label: mb_ucfirst(t('View {type}', ['type' => $pluralType])),
            info: t('Allows viewing existing {type} and creating drafts for them.', [
                'type' => $pluralType,
            ]),
            nested: collect([
                new Permission(
                    key: "createEntries:$section->uid",
                    label: mb_ucfirst(t('Create {type}', ['type' => $pluralType])),
                    info: t('Allows creating drafts of new {type}.', ['type' => $pluralType]),
                ),
                new Permission(
                    key: "saveEntries:$section->uid",
                    label: mb_ucfirst(t('Save {type}', ['type' => $pluralType])),
                    info: t('Allows fully saving canonical {type} (directly or by applying drafts).', [
                        'type' => $pluralType,
                    ]),
                ),
                $hasCustomPropagation ? new Permission(
                    key: "deleteEntriesForSite:$section->uid",
                    label: mb_ucfirst(t('Delete {type} for site', ['type' => $pluralType])),
                    info: t('Allows deleting {type} for individual sites, provided the user has access to them.', [
                        'type' => $pluralType,
                    ]),
                ) : null,
                new Permission(
                    key: "deleteEntries:$section->uid",
                    label: mb_ucfirst(t('Delete {type}', ['type' => $pluralType])),
                    info: t('Allows deleting {type} for all sites.', [
                        'type' => $pluralType,
                    ]),
                ),
                new Permission(
                    key: "viewPeerEntries:$section->uid",
                    label: mb_ucfirst(t('View other users’ {type}', ['type' => $pluralType])),
                    nested: collect([
                        new Permission(
                            key: "savePeerEntries:$section->uid",
                            label: mb_ucfirst(t('Save other users’ {type}', ['type' => $pluralType])),
                        ),
                        $hasCustomPropagation ? new Permission(
                            key: "deletePeerEntriesForSite:$section->uid",
                            label: t('Delete other users’ {type} for site', ['type' => $pluralType]),
                            info: t('Allows deleting other users’ {type} for individual sites, provided the user has access to them.', [
                                'type' => $pluralType,
                            ]),
                        ) : null,
                        new Permission(
                            key: "deletePeerEntries:$section->uid",
                            label: t('Delete other users’ {type}', ['type' => $pluralType]),
                            info: t('Allows deleting other users’ {type} for all sites.', ['type' => $pluralType]),
                        ),
                    ])->filter(),
                ),
                new Permission(
                    key: "viewPeerEntryDrafts:$section->uid",
                    label: mb_ucfirst(t('View other users’ {type}', [
                        'type' => t('drafts'),
                    ])),
                    nested: collect([
                        new Permission(
                            key: "savePeerEntryDrafts:$section->uid",
                            label: mb_ucfirst(t('Save other users’ {type}', [
                                'type' => t('drafts'),
                            ])),
                        ),
                        new Permission(
                            key: "deletePeerEntryDrafts:$section->uid",
                            label: t('Delete other users’ {type}', [
                                'type' => t('drafts'),
                            ])
                        ),
                    ]),
                ),
            ])->filter(),
        );
    }

    private function volumePermissions(Collection $permissions): void
    {
        $volumes = Craft::$app->getVolumes()->getAllVolumes();

        if (! $volumes) {
            return;
        }

        $type = Asset::pluralLowerDisplayName();

        foreach ($volumes as $volume) {
            $permissions->add(new PermissionGroup(
                heading: t('Volume - {volume}', [
                    'volume' => t($volume->name, category: 'site'),
                ]),
                permissions: collect([
                    new Permission(
                        key: "viewAssets:$volume->uid",
                        label: mb_ucfirst(t('View {type}', ['type' => $type])),
                        nested: collect([
                            new Permission(key: "saveAssets:$volume->uid", label: mb_ucfirst(t('Save {type}', ['type' => $type]))),
                            new Permission(key: "deleteAssets:$volume->uid", label: mb_ucfirst(t('Delete {type}', ['type' => $type]))),
                            new Permission(key: "replaceFiles:$volume->uid", label: t('Replace files')),
                            new Permission(key: "editImages:$volume->uid", label: t('Edit images')),
                            new Permission(
                                key: "viewPeerAssets:$volume->uid",
                                label: t('View assets uploaded by other users'),
                                nested: collect([
                                    new Permission(key: "savePeerAssets:$volume->uid", label: t('Save assets uploaded by other users')),
                                    new Permission(
                                        key: "replacePeerFiles:$volume->uid",
                                        label: t('Replace files uploaded by other users'),
                                        warning: t('When someone replaces a file, the record of who uploaded the file will be updated as well.'),
                                    ),
                                    new Permission(key: "deletePeerAssets:$volume->uid", label: t('Remove files uploaded by other users')),
                                    new Permission(key: "editPeerImages:$volume->uid", label: t('Edit images uploaded by other users')),
                                ])
                            ),
                            new Permission(key: "createFolders:$volume->uid", label: t('Create subfolders')),
                        ]),
                    ),
                ]),
            ));
        }
    }

    private function utilityPermissions(Collection $permissions): void
    {
        $permissions->add(new PermissionGroup(
            heading: t('Utilities'),
            permissions: app(Utilities::class)->getAllUtilityTypes()->map(function (string $class) {
                /** @var class-string<Utility> $class */
                // Admins only
                if (ProjectConfigUtility::id() === $class::id()) {
                    return null;
                }

                return new Permission(
                    key: sprintf('utility:%s', $class::id()),
                    label: $class::displayName(),
                );
            })->filter(),
        ));
    }

    /**
     * Filters out any permissions that aren't assignable by the current user.
     *
     * @param  Collection  $permissions  The original permissions
     * @param  User|null  $user  The recipient of the permissions. If set, their current permissions will be included as well.
     * @return Collection The filtered permissions
     */
    private function filterUnassignablePermissions(Collection $permissions, ?User $user = null): Collection
    {
        $currentUser = Auth::user();

        if (! $currentUser && ! $user) {
            return new Collection;
        }

        return $permissions->map(function (Permission $permission) use ($currentUser, $user) {
            if ($currentUser !== null && ! $currentUser->can($permission->key)) {
                return null;
            }

            if ($user !== null && ! $user->can($permission->key)) {
                return null;
            }

            if ($permission->nested->isNotEmpty()) {
                $permission->nested = $this->filterUnassignablePermissions($permission->nested, $user);
            }

            return $permission;
        })->filter();
    }

    /**
     * Filters out any orphaned permissions.
     *
     * @param  array  $postedPermissions  The posted permissions.
     * @param  array  $groupPermissions  Permissions the user is already assigned
     *                                   to via their group, if we’re saving a user’s permissions.
     * @return array The permissions we'll actually let them save.
     */
    private function filterOrphanedPermissions(array $postedPermissions, array $groupPermissions = []): array
    {
        $filteredPermissions = [];

        if (! empty($postedPermissions)) {
            foreach ($this->getAllPermissions() as $group) {
                $this->findSelectedPermissions(
                    permissionsGroup: $group->permissions,
                    postedPermissions: $postedPermissions,
                    groupPermissions: $groupPermissions,
                    filteredPermissions: $filteredPermissions
                );
            }
        }

        return $filteredPermissions;
    }

    /**
     * Iterates through a group of permissions, returning the ones that were selected.
     *
     * @param  Collection<Permission>  $permissionsGroup
     * @return bool Whether any permissions were added to $filteredPermissions
     */
    private function findSelectedPermissions(
        Collection $permissionsGroup,
        array $postedPermissions,
        array $groupPermissions,
        array &$filteredPermissions,
    ): bool {
        $hasAssignedPermissions = false;

        foreach ($permissionsGroup as $permission) {
            $key = strtolower($permission->key);

            // Should the user have this permission (either directly or via their group)?
            if (
                ($inPostedPermissions = in_array($key, $postedPermissions, true))
                || in_array($key, $groupPermissions, true)
            ) {
                // First assign any nested permissions
                if ($permission->nested->isNotEmpty()) {
                    $hasAssignedNestedPermissions = $this->findSelectedPermissions(
                        permissionsGroup: $permission->nested,
                        postedPermissions: $postedPermissions,
                        groupPermissions: $groupPermissions,
                        filteredPermissions: $filteredPermissions,
                    );
                } else {
                    $hasAssignedNestedPermissions = false;
                }

                // Were they assigned this permission (or any of its nested permissions) directly?
                if ($inPostedPermissions || $hasAssignedNestedPermissions) {
                    // Assign the permission directly to the user
                    $filteredPermissions[] = $key;
                    $hasAssignedPermissions = true;
                }
            }
        }

        return $hasAssignedPermissions;
    }

    /**
     * Returns a permission record based on its name. If a record doesn't exist, it will be created.
     */
    private function getPermissionModelByName(string $permissionName): UserPermission
    {
        // Permission names are always stored in lowercase
        $permissionName = strtolower($permissionName);

        return UserPermission::firstOrCreate([
            'name' => $permissionName,
        ]);
    }

    private function createUserPermissionsQuery(): Builder
    {
        return DB::table(Table::USERPERMISSIONS, 'p')->select(['p.name']);
    }

    /**
     * Resets the internal state
     */
    public function reset(): void
    {
        unset(
            $this->allPermissions,
            $this->allPermissionNames,
            $this->permissionsByGroupId,
            $this->permissionsByUserId,
        );
    }
}
