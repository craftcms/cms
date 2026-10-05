<?php

declare(strict_types=1);

namespace CraftCms\Cms\Mcp\Capabilities;

use CraftCms\Cms\Mcp\Attributes\RequiresAdminChanges;
use CraftCms\Cms\Mcp\Attributes\RequiresPermission;
use CraftCms\Cms\Mcp\McpActor;
use CraftCms\Cms\Support\Arr;
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

    /** @return array{groups: list<array<string, mixed>>} */
    #[McpTool(
        name: 'user-permissions.list',
        description: 'Lists known Craft CMS user permissions grouped by category.',
        annotations: new ToolAnnotations(readOnlyHint: true),
    )]
    #[RequiresPermission('assignUserPermissions')]
    public function list(): array
    {
        return [
            'groups' => $this->permissions
                ->getAllPermissions()
                ->map($this->serializePermissionGroup(...))
                ->values()
                ->all(),
        ];
    }

    /**
     * @param  int|null  $userId  User ID.
     * @param  string|null  $userUid  User UID.
     * @param  string|null  $username  Username.
     * @param  string|null  $email  User email address.
     * @return array<string, mixed>
     */
    #[McpTool(
        name: 'user-permissions.user.get',
        description: 'Gets assigned Craft CMS permissions for a user.',
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

    /**
     * @param  int|null  $groupId  User group ID.
     * @param  string|null  $groupUid  User group UID.
     * @param  string|null  $groupHandle  User group handle.
     * @return array<string, mixed>
     */
    #[McpTool(
        name: 'user-permissions.group.get',
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
     * @param  int|null  $userId  User ID.
     * @param  string|null  $userUid  User UID.
     * @param  string|null  $username  Username.
     * @param  string|null  $email  User email address.
     * @return array<string, mixed>
     */
    #[McpTool(
        name: 'user-permissions.user.set',
        description: 'Replaces the directly assigned Craft CMS permissions for a user.',
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

        $this->authorizeAssignablePermissions($permissions, $user);

        if (! $this->permissions->saveUserPermissions($user->id, $permissions)) {
            throw new ToolCallException('User permissions could not be saved.');
        }

        return $this->userPermissions($user->id);
    }

    /**
     * @param  list<string>  $permissions  Complete list of direct permission keys to assign.
     * @param  int|null  $groupId  User group ID.
     * @param  string|null  $groupUid  User group UID.
     * @param  string|null  $groupHandle  User group handle.
     * @return array<string, mixed>
     */
    #[McpTool(
        name: 'user-permissions.group.set',
        description: 'Replaces the directly assigned Craft CMS permissions for a user group.',
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

        $this->authorizeAssignablePermissions($permissions, $group);

        if (! $this->permissions->saveGroupPermissions($group->id, $permissions)) {
            throw new ToolCallException('User group permissions could not be saved.');
        }

        return $this->groupPermissions($group->id);
    }

    /** @return array{groups: list<array<string, mixed>>} */
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

    /** @return array{userId: int, permissions: list<string>} */
    private function userPermissions(int $userId): array
    {
        return [
            'userId' => $userId,
            'permissions' => $this->permissions->getPermissionsByUserId($userId)->values()->all(),
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
