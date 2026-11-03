<?php

declare(strict_types=1);

namespace craft\mcp\capabilities;

use craft\db\Query;
use craft\db\Table;
use craft\elements\User;
use craft\mcp\attributes\RequiresAdminChanges;
use craft\mcp\attributes\RequiresPermission;
use craft\mcp\McpActor;
use craft\models\UserGroup;
use craft\services\UserGroups;
use craft\services\UserPermissions as UserPermissionService;
use craft\services\Users;
use Illuminate\Support\Arr;
use Mcp\Capability\Attribute\McpResource;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Capability\Attribute\Schema;
use Mcp\Exception\ToolCallException;
use Mcp\Schema\ToolAnnotations;

/**
 * @since 5.12.0
 */
class UserPermissions
{
    public function __construct(
        private McpActor $actor,
        private UserPermissionService $permissions,
        private Users $users,
        private UserGroups $userGroups,
    ) {
    }

    /** @return array{count: int, groups: list<array<string, mixed>>} */
    #[McpTool(
        name: 'user-permissions.list',
        description: 'Lists known Craft CMS user permissions grouped by category.',
        annotations: new ToolAnnotations(readOnlyHint: true),
    )]
    #[RequiresPermission('assignUserPermissions')]
    public function list(): array
    {
        $groups = collect($this->permissions->getAllPermissions())
            ->map($this->serializePermissionGroup(...))
            ->values();

        return [
            'count' => $groups->count(),
            'groups' => $groups->all(),
        ];
    }

    /** @return array<string, mixed> */
    #[McpTool(
        name: 'user-permissions.user.get',
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

        if (!$user?->id) {
            throw new ToolCallException('User not found.');
        }

        return $this->userPermissions($user->id);
    }

    /** @return array<string, mixed> */
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

        if (!$group?->id) {
            throw new ToolCallException('User group not found.');
        }

        return $this->groupPermissions($group->id);
    }

    /**
     * @param  list<string>  $permissions  Complete list of direct permission keys to assign.
     * @return array<string, mixed>
     */
    #[McpTool(
        name: 'user-permissions.user.set',
        description: 'Replaces the complete directly assigned permission list for a user. Read user-permissions.user.get and retain keys from directPermissions. An empty list clears direct assignments; group grants remain.',
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

        if (!$user?->id) {
            throw new ToolCallException('User not found.');
        }

        $this->authorizeAssignablePermissions($permissions, $user);

        if (!$this->permissions->saveUserPermissions($user->id, $permissions)) {
            throw new ToolCallException('User permissions could not be saved.');
        }

        return $this->userPermissions($user->id);
    }

    /**
     * @param  list<string>  $permissions  Complete list of direct permission keys to assign.
     * @return array<string, mixed>
     */
    #[McpTool(
        name: 'user-permissions.group.set',
        description: 'Replaces the complete permission list for a user group. Read user-permissions.group.get and include all permissions to retain. An empty list clears it.',
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

        if (!$group?->id) {
            throw new ToolCallException('User group not found.');
        }

        $this->authorizeAssignablePermissions($permissions, $group);

        if (!$this->permissions->saveGroupPermissions($group->id, $permissions)) {
            throw new ToolCallException('User group permissions could not be saved.');
        }

        return $this->groupPermissions($group->id);
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
     * @param  list<string>  $permissions
     */
    private function authorizeAssignablePermissions(array $permissions, User|UserGroup $target): void
    {
        $actor = $this->actor->user();

        $unauthorized = array_values(array_filter(
            $permissions,
            static fn(string $permission): bool => !$target->can($permission) && !$actor->can($permission),
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
            'permissions' => array_values($this->permissions->getPermissionsByUserId($userId)),
            'directPermissions' => (new Query())->select(['p.name'])->from(['p' => Table::USERPERMISSIONS])->innerJoin(['pu' => Table::USERPERMISSIONS_USERS], '[[pu.permissionId]] = [[p.id]]')->where(['pu.userId' => $userId])->column(),
        ];
    }

    /** @return array{groupId: int, permissions: list<string>} */
    private function groupPermissions(int $groupId): array
    {
        return [
            'groupId' => $groupId,
            'permissions' => array_values($this->permissions->getPermissionsByGroupId($groupId)),
        ];
    }

    /** @return array<string, mixed> */
    private function serializePermissionGroup(array $group): array
    {
        return [
            'handle' => $group['handle'] ?? null,
            'heading' => $group['heading'],
            'permissions' => $this->serializePermissions($group['permissions']),
        ];
    }

    /** @return list<array<string, mixed>> */
    private function serializePermissions(array $permissions): array
    {
        $result = [];

        foreach ($permissions as $key => $permission) {
            $result[] = [
                'key' => $key,
                'label' => $permission['label'],
                'info' => $permission['info'] ?? null,
                'warning' => $permission['warning'] ?? null,
                'nested' => $this->serializePermissions($permission['nested'] ?? []),
            ];
        }

        return $result;
    }
}
