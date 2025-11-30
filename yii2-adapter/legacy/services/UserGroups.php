<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\services;

use Craft;
use craft\elements\User;
use craft\events\UserGroupEvent;
use craft\models\UserGroup;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Edition;
use CraftCms\Cms\Edition\Exceptions\WrongEditionException;
use CraftCms\Cms\ProjectConfig\Events\ConfigEvent;
use CraftCms\Cms\ProjectConfig\ProjectConfig;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\User\Models\UserGroup as UserGroupModel;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Tpetry\QueryExpressions\Language\Alias;
use yii\base\Component;

/**
 * User Groups service.
 *
 * An instance of the service is available via [[\craft\base\ApplicationTrait::getUserGroups()|`Craft::$app->getUserGroups()`]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class UserGroups extends Component
{
    /**
     * @event UserGroupEvent The event that is triggered before a user group is saved.
     */
    public const EVENT_BEFORE_SAVE_USER_GROUP = 'beforeSaveUserGroup';

    /**
     * @event UserGroupEvent The event that is triggered after a user group is saved.
     */
    public const EVENT_AFTER_SAVE_USER_GROUP = 'afterSaveUserGroup';

    /**
     * @event UserGroupEvent The event that is triggered before a user group is deleted.
     */
    public const EVENT_BEFORE_DELETE_USER_GROUP = 'beforeDeleteUserGroup';

    /**
     * @event UserGroupEvent The event that is triggered before a user group delete is applied to the database.
     * @since 3.1.0
     */
    public const EVENT_BEFORE_APPLY_GROUP_DELETE = 'beforeApplyGroupDelete';

    /**
     * @event UserGroupEvent The event that is triggered after a user group is saved.
     */
    public const EVENT_AFTER_DELETE_USER_GROUP = 'afterDeleteUserGroup';

    /**
     * The “Team” group’s UUID.
     */
    private const TEAM_GROUP_UUID = 'c55ca0a9-4bd6-409b-afd8-c1884dafecd0';

    /**
     * Returns all user groups.
     *
     * @return UserGroup[]
     */
    public function getAllGroups(): array
    {
        switch (Edition::get()) {
            case Edition::Solo:
                return [];
            case Edition::Team:
                return [$this->getTeamGroup()];
            default:
                return $this->_createUserGroupsQuery()
                    ->orderBy('name')
                    ->get()
                    ->map(fn(object $group) => new UserGroup((array)$group))
                    ->all();
        }
    }

    /**
     * Returns the user groups that the current user is allowed to assign to another user.
     *
     * @param User|null $user The recipient of the user groups. If set, their current groups will be included as well.
     *
     * @return UserGroup[]
     */
    public function getAssignableGroups(?User $user = null): array
    {
        $currentUser = Craft::$app->getUser()->getIdentity();
        if (!$currentUser && !$user) {
            return [];
        }

        // If either user is an admin, all groups are fair game
        if (($currentUser !== null && $currentUser->admin) || ($user !== null && $user->admin)) {
            return $this->getAllGroups();
        }

        $assignableGroups = [];

        foreach ($this->getAllGroups() as $group) {
            if (
                ($currentUser !== null && $currentUser->can("assignUserGroup:$group->uid")) ||
                ($user !== null && $user->isInGroup($group))
            ) {
                $assignableGroups[] = $group;
            }
        }

        return $assignableGroups;
    }

    /**
     * Gets a user group by its ID.
     *
     * @param int $groupId
     *
     * @return UserGroup|null
     */
    public function getGroupById(int $groupId): ?UserGroup
    {
        $result = $this->_createUserGroupsQuery()
            ->where('id', $groupId)
            ->first();

        return $result ? new UserGroup((array) $result) : null;
    }

    /**
     * Gets a user group by its UID.
     *
     * @param string $uid
     *
     * @return UserGroup|null
     */
    public function getGroupByUid(string $uid): ?UserGroup
    {
        $result = $this->_createUserGroupsQuery()
            ->where('uid', $uid)
            ->first();

        return $result ? new UserGroup((array) $result) : null;
    }

    /**
     * Gets a user group by its handle.
     *
     * @param string $groupHandle
     *
     * @return UserGroup|null
     */
    public function getGroupByHandle(string $groupHandle): ?UserGroup
    {
        $result = $this->_createUserGroupsQuery()
            ->where('handle', $groupHandle)
            ->first();

        return $result ? new UserGroup((array) $result) : null;
    }

    /**
     * Returns the Craft Team edition’s user group.
     *
     * @return UserGroup
     * @since 5.1.0
     */
    public function getTeamGroup(): UserGroup
    {
        Edition::require(Edition::Team, false);

        $group = $this->getGroupByUid(self::TEAM_GROUP_UUID);
        if ($group) {
            return $group;
        }

        /** @var UserGroup $group */
        $group = Craft::createObject([
            'class' => UserGroup::class,
            'uid' => self::TEAM_GROUP_UUID,
        ]);

        // Find a unique name + handle
        $i = 1;
        do {
            $group->name = sprintf('Team%s', $i > 1 ? " $i" : '');
            $group->handle = sprintf('team%s', $i > 1 ? $i : '');
            if ($group->validate(['name', 'handle'])) {
                break;
            }
            $i++;
        } while (true);

        $groupModel = UserGroupModel::findByUid($group->uid) ?? new UserGroupModel();
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
     * @param int $userId
     *
     * @return UserGroup[]
     */
    public function getGroupsByUserId(int $userId): array
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
            ->map(fn(object $group) => new UserGroup((array)$group))
            ->all();
    }

    /**
     * Eager-loads user groups onto the given users.
     *
     * @param User[] $users The users to eager-load user groups onto
     *
     * @since 3.6.0
     */
    public function eagerLoadGroups(array $users): void
    {
        if (empty($users)) {
            return;
        }

        $assignments = DB::table(Table::USERGROUPS_USERS)
            ->select(['groupId', 'userId'])
            ->whereIn('userId', array_unique(array_map(fn(User $user) => $user->id, $users)))
            ->get();

        $groupsByUserId = [];

        if ($assignments->isNotEmpty()) {
            // Get the user groups, indexed by their IDs
            $groups = $this->_createUserGroupsQuery()
                ->whereIn('id', $assignments->pluck('groupId')->unique())
                ->get()
                ->keyBy('id')
                ->map(fn(object $result) => new UserGroup((array) $result))
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
     * @param UserGroup $group The user group to be saved
     * @param bool $runValidation Whether the user group should be validated
     *
     * @return bool
     * @throws WrongEditionException if this is called from Craft Solo edition
     */
    public function saveGroup(UserGroup $group, bool $runValidation = true): bool
    {
        if ($group->uid === self::TEAM_GROUP_UUID) {
            Edition::require(Edition::Team);
        } else {
            Edition::require(Edition::Pro);
        }

        $isNewGroup = !$group->id;

        // Fire a 'beforeSaveUserGroup' event
        if ($this->hasEventHandlers(self::EVENT_BEFORE_SAVE_USER_GROUP)) {
            $this->trigger(self::EVENT_BEFORE_SAVE_USER_GROUP, new UserGroupEvent([
                'userGroup' => $group,
                'isNew' => $isNewGroup,
            ]));
        }

        if ($runValidation && !$group->validate()) {
            Craft::info('User group not saved due to validation error.', __METHOD__);
            return false;
        }

        $projectConfig = app(ProjectConfig::class);

        if (!$group->uid) {
            if ($isNewGroup) {
                $group->uid = Str::uuid()->toString();
            } elseif (!$group->uid) {
                $group->uid = DB::table(Table::USERGROUPS)->uidById($group->id);
            }
        }

        $configPath = ProjectConfig::PATH_USER_GROUPS . '.' . $group->uid;
        $configData = $group->getConfig(false);
        $projectConfig->set($configPath, $configData, "Save user group “{$group->handle}”");

        // Now that we have a group ID, save it on the model
        if ($isNewGroup) {
            $group->id = DB::table(Table::USERGROUPS)->idByUid($group->uid);
        }

        return true;
    }

    /**
     * Handle any changed user groups.
     *
     * @param ConfigEvent $event
     */
    public function handleChangedUserGroup(ConfigEvent $event): void
    {
        $uid = $event->tokenMatches[0];
        $data = $event->newValue;

        $groupModel = UserGroupModel::findByUid($uid) ?? new UserGroupModel();
        $isNewGroup = !$groupModel->exists;

        $groupModel->name = $data['name'];
        $groupModel->handle = $data['handle'];
        $groupModel->description = $data['description'] ?? null;
        $groupModel->uid = $uid;
        $groupModel->save();

        // Prevent permission information from being saved. Allowing it would prevent the appropriate event from firing.
        $event->newValue['permissions'] = $event->oldValue['permissions'] ?? [];

        // Fire an 'afterSaveUserGroup' event
        if ($this->hasEventHandlers(self::EVENT_AFTER_SAVE_USER_GROUP)) {
            $this->trigger(self::EVENT_AFTER_SAVE_USER_GROUP, new UserGroupEvent([
                'userGroup' => $this->getGroupById($groupModel->id),
                'isNew' => $isNewGroup,
            ]));
        }

        // Invalidate user caches
        Craft::$app->getElements()->invalidateCachesForElementType(User::class);
    }

    /**
     * Handle any deleted user groups.
     *
     * @param ConfigEvent $event
     */
    public function handleDeletedUserGroup(ConfigEvent $event): void
    {
        $uid = $event->tokenMatches[0];

        $group = $this->getGroupByUid($uid);

        if (!$group) {
            // the group must already be deleted
            return;
        }

        // Fire a 'beforeApplyGroupDelete' event
        if ($this->hasEventHandlers(self::EVENT_BEFORE_APPLY_GROUP_DELETE)) {
            $this->trigger(self::EVENT_BEFORE_APPLY_GROUP_DELETE, new UserGroupEvent([
                'userGroup' => $group,
            ]));
        }

        DB::table(Table::USERGROUPS)
            ->where('uid', $uid)
            ->delete();

        // Fire an 'afterDeleteUserGroup' event
        if ($this->hasEventHandlers(self::EVENT_AFTER_DELETE_USER_GROUP)) {
            $this->trigger(self::EVENT_AFTER_DELETE_USER_GROUP, new UserGroupEvent([
                'userGroup' => $group,
            ]));
        }

        // Invalidate user caches
        Craft::$app->getElements()->invalidateCachesForElementType(User::class);
    }

    /**
     * Deletes a user group by its ID.
     *
     * @param int $groupId The user group's ID
     *
     * @return bool Whether the user group was deleted successfully
     * @throws WrongEditionException if this is called from Craft Solo edition
     */
    public function deleteGroupById(int $groupId): bool
    {
        Edition::require(Edition::Pro);

        $group = $this->getGroupById($groupId);

        if (!$group) {
            return false;
        }

        return $this->deleteGroup($group);
    }

    /**
     * Deletes a user group.
     *
     * @param UserGroup $group The user group
     *
     * @return bool Whether the user group was deleted successfully
     * @throws WrongEditionException if this is called from Craft Solo edition
     * @since 3.0.12
     */
    public function deleteGroup(UserGroup $group): bool
    {
        Edition::require(Edition::Pro);

        // Fire a 'beforeDeleteUserGroup' event
        if ($this->hasEventHandlers(self::EVENT_BEFORE_DELETE_USER_GROUP)) {
            $this->trigger(self::EVENT_BEFORE_DELETE_USER_GROUP, new UserGroupEvent([
                'userGroup' => $group,
            ]));
        }

        app(ProjectConfig::class)->remove(ProjectConfig::PATH_USER_GROUPS . '.' . $group->uid,
            "Delete the “{$group->handle}” user group");
        return true;
    }

    private function _createUserGroupsQuery(): Builder
    {
        return DB::table(Table::USERGROUPS)
            ->select([
                'id',
                'name',
                'handle',
                'description',
                'uid',
            ]);
    }
}
