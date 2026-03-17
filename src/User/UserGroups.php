<?php

declare(strict_types=1);

namespace CraftCms\Cms\User;

use Craft;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Edition;
use CraftCms\Cms\Edition\Exceptions\WrongEditionException;
use CraftCms\Cms\ProjectConfig\Events\ConfigEvent;
use CraftCms\Cms\ProjectConfig\ProjectConfig;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\User\Data\UserGroup;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\User\Events\ApplyingUserGroupDelete;
use CraftCms\Cms\User\Events\DeletingUserGroup;
use CraftCms\Cms\User\Events\SavingUserGroup;
use CraftCms\Cms\User\Events\UserGroupDeleted;
use CraftCms\Cms\User\Events\UserGroupSaved;
use CraftCms\Cms\User\Models\UserGroup as UserGroupModel;
use Illuminate\Container\Attributes\Singleton;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Tpetry\QueryExpressions\Language\Alias;

#[Singleton]
readonly class UserGroups
{
    /**
     * The “Team” group’s UUID.
     */
    private const string TEAM_GROUP_UUID = 'c55ca0a9-4bd6-409b-afd8-c1884dafecd0';

    public function __construct(
        private ProjectConfig $projectConfig,
    ) {}

    /**
     * Returns all user groups.
     *
     * @return Collection<UserGroup>
     */
    public function getAllGroups(): Collection
    {
        return match (Edition::get()) {
            Edition::Solo => collect(),
            Edition::Team => collect([$this->getTeamGroup()]),
            default => $this->createUserGroupsQuery()
                ->orderBy('name')
                ->get()
                ->mapInto(UserGroup::class)
        };
    }

    /**
     * Returns the user groups that the current user is allowed to assign to another user.
     *
     * @param  User|null  $user  The recipient of the user groups. If set, their current groups will be included as well.
     * @return Collection<UserGroup>
     */
    public function getAssignableGroups(?User $user = null): Collection
    {
        $currentUser = Auth::user();

        if (! $currentUser && ! $user) {
            return collect();
        }

        // If either user is an admin, all groups are fair game
        if (($currentUser !== null && $currentUser->admin) || ($user !== null && $user->admin)) {
            return $this->getAllGroups();
        }

        return $this->getAllGroups()->filter(function (UserGroup $group) use ($currentUser, $user) {
            if ($currentUser !== null && $currentUser->can("assignUserGroup:$group->uid")) {
                return true;
            }

            if ($user !== null && $user->isInGroup($group)) {
                return true;
            }

            return false;
        });
    }

    public function getGroupById(int $groupId): ?UserGroup
    {
        $result = $this->createUserGroupsQuery()->find($groupId);

        return $result ? new UserGroup((array) $result) : null;
    }

    public function getGroupByUid(string $uid): ?UserGroup
    {
        $result = $this->createUserGroupsQuery()
            ->where('uid', $uid)
            ->first();

        return $result ? new UserGroup((array) $result) : null;
    }

    public function getGroupByHandle(string $groupHandle): ?UserGroup
    {
        $result = $this->createUserGroupsQuery()
            ->where('handle', $groupHandle)
            ->first();

        return $result ? new UserGroup((array) $result) : null;
    }

    public function resolveGroup(mixed $group): ?UserGroup
    {
        return match (true) {
            is_int($group) => $this->getGroupById($group),
            is_string($group) && ctype_digit($group) => $this->getGroupById((int) $group),
            is_string($group) && Str::isUuid($group) => $this->getGroupByUid($group),
            is_string($group) && trim($group) !== '' => $this->getGroupByHandle(trim($group)),
            default => null,
        };
    }

    /**
     * Returns the Craft Team edition’s user group.
     */
    public function getTeamGroup(): UserGroup
    {
        Edition::require(Edition::Team, false);

        if ($group = $this->getGroupByUid(self::TEAM_GROUP_UUID)) {
            return $group;
        }

        $group = new UserGroup([
            'uid' => self::TEAM_GROUP_UUID,
        ]);

        // Find a unique name + handle
        $i = 1;
        do {
            $group->name = sprintf('Team%s', $i > 1 ? " $i" : '');
            $group->handle = sprintf('team%s', $i > 1 ? $i : '');

            try {
                Validator::validate(
                    ['name' => $group->name, 'handle' => $group->handle],
                    $group->getRules(),
                );
                break;
            } catch (ValidationException) {
                // Continue
            }

            $i++;
        } while (true);

        $groupModel = UserGroupModel::findByUid($group->uid) ?? new UserGroupModel;
        $groupModel->name = $group->name;
        $groupModel->handle = $group->handle;
        $groupModel->description = null;
        $groupModel->uid = $group->uid;
        $groupModel->save();

        $group->id = $groupModel->id;

        return $group;
    }

    /**
     * Gets user groups by a user ID.
     *
     * @return Collection<UserGroup>
     */
    public function getGroupsByUserId(int $userId): Collection
    {
        return DB::table(Table::USERGROUPS, 'g')
            ->select([
                'g.id',
                'g.name',
                'g.handle',
                'g.description',
                'g.uid',
            ])
            ->join(new Alias(Table::USERGROUPS_USERS, 'gu'), 'gu.groupId', 'g.id')
            ->where('gu.userId', $userId)
            ->get()
            ->mapInto(UserGroup::class);
    }

    /**
     * Eager-loads user groups onto the given users.
     *
     * @param  User[]  $users  The users to eager-load user groups onto
     */
    public function eagerLoadGroups(array $users): void
    {
        if (empty($users)) {
            return;
        }

        $assignments = DB::table(Table::USERGROUPS_USERS)
            ->select(['groupId', 'userId'])
            ->whereIn('userId', array_unique(array_map(fn (User $user) => $user->id, $users)))
            ->get();

        $groupsByUserId = [];

        if ($assignments->isNotEmpty()) {
            // Get the user groups, indexed by their IDs
            $groups = $this->createUserGroupsQuery()
                ->whereIn('id', $assignments->pluck('groupId')->unique())
                ->get()
                ->keyBy('id')
                ->map(fn (object $result) => new UserGroup((array) $result))
                ->all();

            // Create batches of user groups by user ID
            foreach ($assignments as $assignment) {
                if (isset($groups[$assignment['groupId']])) {
                    $groupsByUserId[$assignment['userId']][] = $groups[$assignment['groupId']];
                }
            }
        }

        // Assign the user groups
        foreach ($users as $user) {
            $user->setGroups($groupsByUserId[$user->id] ?? []);
        }
    }

    /**
     * Saves a user group.
     *
     * @param  UserGroup  $group  The user group to be saved
     *
     * @throws WrongEditionException if this is called from Craft Solo edition
     */
    public function saveGroup(UserGroup $group): bool
    {
        Edition::require($group->uid === self::TEAM_GROUP_UUID
            ? Edition::Team
            : Edition::Pro
        );

        $isNewGroup = ! $group->id;

        event(new SavingUserGroup($group, $isNewGroup));

        $group->uid ??= $isNewGroup
            ? Str::uuid()->toString()
            : DB::table(Table::USERGROUPS)->uidById($group->id);

        $configPath = ProjectConfig::PATH_USER_GROUPS.'.'.$group->uid;
        $configData = $group->getConfig(withPermissions: false);
        $this->projectConfig->set($configPath, $configData, "Save user group “{$group->handle}”");

        // Now that we have a group ID, save it on the model
        if ($isNewGroup) {
            $group->id = DB::table(Table::USERGROUPS)->idByUid($group->uid);
        }

        return true;
    }

    /**
     * Handle any changed user groups.
     */
    public function handleChangedUserGroup(ConfigEvent $event): void
    {
        $uid = $event->tokenMatches[0];
        $data = $event->newValue;

        $groupModel = UserGroupModel::findByUid($uid) ?? new UserGroupModel;
        $isNewGroup = ! $groupModel->exists;

        $groupModel->name = $data['name'];
        $groupModel->handle = $data['handle'];
        $groupModel->description = $data['description'] ?? null;
        $groupModel->uid = $uid;
        $groupModel->save();

        // Prevent permission information from being saved. Allowing it would prevent the appropriate event from firing.
        $event->newValue['permissions'] = $event->oldValue['permissions'] ?? [];

        event(new UserGroupSaved($this->getGroupById($groupModel->id), $isNewGroup));

        // Invalidate user caches
        Craft::$app->getElements()->invalidateCachesForElementType(User::class);
    }

    /**
     * Handle any deleted user groups.
     */
    public function handleDeletedUserGroup(ConfigEvent $event): void
    {
        $uid = $event->tokenMatches[0];

        $group = $this->getGroupByUid($uid);

        if (! $group) {
            // the group must already be deleted
            return;
        }

        event(new ApplyingUserGroupDelete($group));

        DB::table(Table::USERGROUPS)->where('uid', $uid)->delete();

        event(new UserGroupDeleted($group));

        // Invalidate user caches
        Craft::$app->getElements()->invalidateCachesForElementType(User::class);
    }

    public function deleteGroupById(int $groupId): bool
    {
        Edition::require(Edition::Pro);

        $group = $this->getGroupById($groupId);

        if (! $group) {
            return false;
        }

        return $this->deleteGroup($group);
    }

    public function deleteGroup(UserGroup $group): bool
    {
        Edition::require(Edition::Pro);

        event(new DeletingUserGroup($group));

        $this->projectConfig->remove(
            ProjectConfig::PATH_USER_GROUPS.'.'.$group->uid,
            "Delete the “{$group->handle}” user group",
        );

        return true;
    }

    private function createUserGroupsQuery(): Builder
    {
        return DB::table(Table::USERGROUPS)->select([
            'id',
            'name',
            'handle',
            'description',
            'uid',
        ]);
    }
}
