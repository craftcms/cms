<?php

declare(strict_types=1);

namespace CraftCms\Cms\Mcp\Capabilities;

use CraftCms\Cms\Mcp\Attributes\RequiresAdminChanges;
use CraftCms\Cms\Mcp\Attributes\RequiresPermission;
use CraftCms\Cms\Mcp\McpActor;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\User\Data\Permission;
use CraftCms\Cms\User\Data\PermissionGroup;
use CraftCms\Cms\User\Data\UserGroup;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\User\UserGroups;
use CraftCms\Cms\User\UserPermissions as UserPermissionService;
use CraftCms\Cms\User\Users;
use Illuminate\Support\Facades\Gate;
use Mcp\Capability\Attribute\McpResource;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Capability\Attribute\Schema;
use Mcp\Exception\ToolCallException;
use Mcp\Schema\ToolAnnotations;

/**
 * @since 6.0.0
 */
readonly class UserPermissions
{
    public function __construct(
        private McpActor $actor,
        private UserPermissionService $permissions,
        private Users $users,
        private UserGroups $userGroups,
    ) {}

    /** @return array{count: int, groups: list<array<string, mixed>>} */
    #[McpTool(
        name: 'user_permissions_list',
        description: 'Lists known Craft CMS user permissions grouped by category.',
        annotations: new ToolAnnotations(readOnlyHint: true),
    )]
    #[RequiresPermission('assignUserPermissions')]
    public function list(): array
    {
        $groups = $this->permissions
            ->getAllPermissions()
            ->map($this->serializePermissionGroup(...))
            ->values();

        return [
            'count' => $groups->count(),
            'groups' => $groups->all(),
        ];
    }

    /** @return array<string, mixed> */
    #[McpTool(
        name: 'user_permissions_user_get',
        description: 'Gets a user’s effective permissions, including group grants, and separately returns directPermissions for editing direct assignments.',
        annotations: new ToolAnnotations(readOnlyHint: true),
    )]
    #[RequiresPermission('assignUserPermissions')]
    public function getUser(
        ?int $userId = null,
        #[Schema(format: 'uuid')]
        ?string $userUid = null,
        ?string $username = null,
        #[Schema(format: 'email')]
        ?string $email = null,
    ): array {
        $user = $this->findUser($userId, $userUid, $username, $email);

        if (! $user?->id) {
            throw new ToolCallException('User not found.');
        }

        return $this->userPermissions($user->id);
    }

    /** @return array<string, mixed> */
    #[McpTool(
        name: 'user_permissions_group_get',
        description: 'Gets assigned Craft CMS permissions for a user group.',
        annotations: new ToolAnnotations(readOnlyHint: true),
    )]
    #[RequiresPermission('assignUserPermissions')]
    public function getGroup(
        ?int $groupId = null,
        #[Schema(format: 'uuid')]
        ?string $groupUid = null,
        ?string $groupHandle = null,
    ): array {
        $group = $this->findGroup($groupId, $groupUid, $groupHandle);

        if (! $group?->id) {
            throw new ToolCallException('User group not found.');
        }

        return $this->groupPermissions($group->id);
    }

    /**
     * @param  list<string>  $permissions  Complete list of direct permission keys to assign.
     * @return array<string, mixed>
     */
    #[McpTool(
        name: 'user_permissions_user_set',
        description: 'Replaces the complete directly assigned permission list for a user. Read user_permissions_user_get and retain keys from directPermissions. An empty list clears direct assignments; group grants remain.',
        annotations: new ToolAnnotations(destructiveHint: true),
    )]
    #[RequiresPermission('assignUserPermissions')]
    public function setUser(
        #[Schema(items: ['type' => 'string'])]
        array $permissions,
        ?int $userId = null,
        #[Schema(format: 'uuid')]
        ?string $userUid = null,
        ?string $username = null,
        #[Schema(format: 'email')]
        ?string $email = null,
    ): array {
        $user = $this->findUser($userId, $userUid, $username, $email);

        if (! $user?->id) {
            throw new ToolCallException('User not found.');
        }

        $this->rejectUnknownPermissions($permissions, $this->permissions->getDirectPermissionsByUserId($user->id)->all());
        $this->authorizeAssignablePermissions($permissions, $user);

        if (! $this->permissions->saveUserPermissions($user->id, $permissions)) {
            throw new ToolCallException('User permissions could not be saved.');
        }

        return $this->userPermissions($user->id);
    }

    /**
     * @param  list<string>  $permissions  Complete list of direct permission keys to assign.
     * @return array<string, mixed>
     */
    #[McpTool(
        name: 'user_permissions_group_set',
        description: 'Replaces the complete permission list for a user group. Read user_permissions_group_get and include all permissions to retain. An empty list clears it.',
        annotations: new ToolAnnotations(destructiveHint: true),
    )]
    #[RequiresAdminChanges]
    #[RequiresPermission('assignUserPermissions')]
    public function setGroup(
        #[Schema(items: ['type' => 'string'])]
        array $permissions,
        ?int $groupId = null,
        #[Schema(format: 'uuid')]
        ?string $groupUid = null,
        ?string $groupHandle = null,
    ): array {
        $group = $this->findGroup($groupId, $groupUid, $groupHandle);

        if (! $group?->id) {
            throw new ToolCallException('User group not found.');
        }

        $this->rejectUnknownPermissions($permissions, $this->permissions->getPermissionsByGroupId($group->id)->all());
        $this->authorizeAssignablePermissions($permissions, $group);

        if (! $this->permissions->saveGroupPermissions($group->id, $permissions)) {
            throw new ToolCallException('User group permissions could not be saved.');
        }

        return $this->groupPermissions($group->id);
    }

    /**
     * @param  list<string>  $add  Direct permission keys to add.
     * @param  list<string>  $remove  Direct permission keys to remove.
     * @return array<string, mixed>
     */
    #[McpTool(
        name: 'user_permissions_user_update',
        description: 'Adds and removes direct permissions for a user, keeping their other direct permissions. Group grants remain.',
        annotations: new ToolAnnotations(destructiveHint: true),
    )]
    #[RequiresPermission('assignUserPermissions')]
    public function updateUser(
        #[Schema(items: ['type' => 'string'])]
        array $add = [],
        #[Schema(items: ['type' => 'string'])]
        array $remove = [],
        ?int $userId = null,
        #[Schema(format: 'uuid')]
        ?string $userUid = null,
        ?string $username = null,
        #[Schema(format: 'email')]
        ?string $email = null,
    ): array {
        $user = $this->findUser($userId, $userUid, $username, $email);

        if (! $user?->id) {
            throw new ToolCallException('User not found.');
        }

        $current = $this->permissions->getDirectPermissionsByUserId($user->id)->all();

        $this->rejectUnknownPermissions($remove, $current);

        return $this->setUser($this->applyChanges($current, $add, $remove), userId: $user->id);
    }

    /**
     * @param  list<string>  $add  Permission keys to add.
     * @param  list<string>  $remove  Permission keys to remove.
     * @return array<string, mixed>
     */
    #[McpTool(
        name: 'user_permissions_group_update',
        description: 'Adds and removes permissions for a user group, keeping its other permissions.',
        annotations: new ToolAnnotations(destructiveHint: true),
    )]
    #[RequiresAdminChanges]
    #[RequiresPermission('assignUserPermissions')]
    public function updateGroup(
        #[Schema(items: ['type' => 'string'])]
        array $add = [],
        #[Schema(items: ['type' => 'string'])]
        array $remove = [],
        ?int $groupId = null,
        #[Schema(format: 'uuid')]
        ?string $groupUid = null,
        ?string $groupHandle = null,
    ): array {
        $group = $this->findGroup($groupId, $groupUid, $groupHandle);

        if (! $group?->id) {
            throw new ToolCallException('User group not found.');
        }

        $current = $this->permissions->getPermissionsByGroupId($group->id)->all();

        $this->rejectUnknownPermissions($remove, $current);

        return $this->setGroup($this->applyChanges($current, $add, $remove), groupId: $group->id);
    }

    /** @return array{count: int, groups: list<array<string, mixed>>} */
    #[McpResource(
        uri: 'craft://user-permissions',
        name: 'craft-user-permissions',
        title: 'Craft User Permissions',
        description: 'A JSON list of known Craft CMS user permissions grouped by category.',
        mimeType: 'application/json',
    )]
    #[RequiresPermission('assignUserPermissions')]
    public function resource(): array
    {
        return $this->list();
    }

    /**
     * @param  list<string>  $current
     * @param  list<string>  $add
     * @param  list<string>  $remove
     * @return list<string>
     */
    private function applyChanges(array $current, array $add, array $remove): array
    {
        $remove = array_map(Str::lower(...), $remove);

        return array_values(array_filter(
            [...$current, ...$add],
            static fn (string $permission): bool => ! in_array(Str::lower($permission), $remove, true),
        ));
    }

    /**
     * Rejects permissions that aren’t registered and aren’t already assigned, rather than letting saving drop them.
     *
     * @param  list<string>  $permissions
     * @param  list<string>  $assigned
     */
    private function rejectUnknownPermissions(array $permissions, array $assigned): void
    {
        $assigned = array_map(Str::lower(...), $assigned);

        $unknown = array_values(array_filter(
            $permissions,
            fn (string $permission): bool => ! $this->permissions->validatePermission($permission)
                && ! in_array(Str::lower($permission), $assigned, true),
        ));

        if ($unknown !== []) {
            throw new ToolCallException(sprintf(
                'Unknown permissions: %s. Call user_permissions_list for the permissions that can be assigned.',
                implode(', ', $unknown),
            ));
        }
    }

    /**
     * @param  list<string>  $permissions
     */
    private function authorizeAssignablePermissions(array $permissions, User|UserGroup $target): void
    {
        $actor = $this->actor->user();

        $unauthorized = array_values(array_filter(
            $permissions,
            static fn (string $permission): bool => $target instanceof User
                ? ! Gate::forUser($actor)->allows('assignPermission', [$target, $permission])
                : ! $target->can($permission) && ! Gate::forUser($actor)->allows($permission),
        ));

        if ($unauthorized !== []) {
            throw new ToolCallException(sprintf(
                'You are not authorized to assign permissions you do not have: %s.',
                implode(', ', $unauthorized),
            ));
        }
    }

    private function findUser(
        ?int $id,
        ?string $uid,
        ?string $username,
        ?string $email,
    ): ?User {
        if (count(Arr::whereNotNull([$id, $uid, $username, $email])) !== 1) {
            return null;
        }

        return match (true) {
            $id !== null => $this->users->getUserById($id),
            $uid !== null => $this->users->getUserByUid($uid),
            $username !== null => $this->users->getUserByUsernameOrEmail($username),
            $email !== null => $this->users->getUserByUsernameOrEmail($email),
            default => null,
        };
    }

    private function findGroup(?int $id, ?string $uid, ?string $handle): ?UserGroup
    {
        if (count(Arr::whereNotNull([$id, $uid, $handle])) !== 1) {
            return null;
        }

        return match (true) {
            $id !== null => $this->userGroups->getGroupById($id),
            $uid !== null => $this->userGroups->getGroupByUid($uid),
            $handle !== null => $this->userGroups->getGroupByHandle($handle),
            default => null,
        };
    }

    /** @return array{userId: int, permissions: list<string>, directPermissions: list<string>} */
    private function userPermissions(int $userId): array
    {
        return [
            'userId' => $userId,
            'permissions' => $this->permissions->getPermissionsByUserId($userId)->values()->all(),
            'directPermissions' => $this->permissions->getDirectPermissionsByUserId($userId)->values()->all(),
        ];
    }

    /** @return array{groupId: int, permissions: list<string>} */
    private function groupPermissions(int $groupId): array
    {
        return [
            'groupId' => $groupId,
            'permissions' => $this->permissions->getPermissionsByGroupId($groupId)->values()->all(),
        ];
    }

    /** @return array<string, mixed> */
    private function serializePermissionGroup(PermissionGroup $group): array
    {
        return [
            'handle' => $group->handle,
            'heading' => $group->heading,
            'permissions' => $group->permissions
                ->map($this->serializePermission(...))
                ->values()
                ->all(),
        ];
    }

    /** @return array<string, mixed> */
    private function serializePermission(Permission $permission): array
    {
        return [
            'key' => $permission->key,
            'label' => $permission->label,
            'info' => $permission->info,
            'warning' => $permission->warning,
            'nested' => $permission->nested
                ->map($this->serializePermission(...))
                ->values()
                ->all(),
        ];
    }
}
