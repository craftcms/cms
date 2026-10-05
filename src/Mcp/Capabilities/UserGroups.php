<?php

declare(strict_types=1);

namespace CraftCms\Cms\Mcp\Capabilities;

use CraftCms\Cms\Mcp\Attributes\RequiresAdmin;
use CraftCms\Cms\Mcp\Attributes\RequiresAdminChanges;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\Support\Typecast;
use CraftCms\Cms\User\Data\UserGroup;
use CraftCms\Cms\User\UserGroups as UserGroupService;
use Mcp\Capability\Attribute\McpResource;
use Mcp\Capability\Attribute\McpResourceTemplate;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Capability\Attribute\Schema;
use Mcp\Exception\ResourceReadException;
use Mcp\Exception\ToolCallException;
use Mcp\Schema\Request\CallToolRequest;
use Mcp\Schema\ToolAnnotations;
use Mcp\Server\RequestContext;

/**
 * @since 6.0.0
 */
readonly class UserGroups
{
    public function __construct(private UserGroupService $userGroups) {}

    /** @return array{groups: list<array<string, mixed>>} */
    #[McpTool(
        name: 'user-groups.list',
        description: 'Lists Craft CMS user groups.',
        annotations: new ToolAnnotations(readOnlyHint: true),
    )]
    #[RequiresAdmin]
    public function list(): array
    {
        return [
            'groups' => $this->userGroups
                ->getAllGroups()
                ->map($this->serialize(...))
                ->values()
                ->all(),
        ];
    }

    /**
     * @param  int|null  $id  User group ID.
     * @param  string|null  $uid  User group UID.
     * @param  string|null  $handle  User group handle.
     * @return array{group: array<string, mixed>}
     */
    #[McpTool(
        name: 'user-groups.get',
        description: 'Gets a Craft CMS user group by ID, UID, or handle.',
        annotations: new ToolAnnotations(readOnlyHint: true),
    )]
    #[RequiresAdmin]
    public function get(
        ?int $id = null,
        #[Schema(format: 'uuid')]
        ?string $uid = null,
        ?string $handle = null,
    ): array {
        if (count(Arr::whereNotNull([$id, $uid, $handle])) !== 1) {
            throw new ToolCallException('Provide exactly one of: id, uid, handle.');
        }

        $group = $this->find($id, $uid, $handle);

        if (! $group) {
            throw new ToolCallException('User group not found.');
        }

        return ['group' => $this->serialize($group)];
    }

    /** @return array{group: array<string, mixed>} */
    #[McpTool(
        name: 'user-groups.create',
        description: 'Creates a Craft CMS user group. Assign permissions separately with user-permissions.set.',
    )]
    #[RequiresAdminChanges]
    public function create(
        string $name,
        string $handle,
        ?string $description = null,
    ): array {
        $group = new UserGroup([
            'name' => $name,
            'handle' => $handle,
            'description' => $description,
        ]);

        if (! $this->userGroups->saveGroup($group)) {
            throw new ToolCallException($this->validationErrors($group));
        }

        return ['group' => $this->serialize($group)];
    }

    /**
     * @param  int|null  $id  User group ID.
     * @param  string|null  $uid  User group UID.
     * @param  string|null  $currentHandle  Existing user group handle.
     * @return array{group: array<string, mixed>}
     */
    #[McpTool(
        name: 'user-groups.update',
        description: 'Updates a Craft CMS user group. Assign permissions separately with user-permissions.set.',
        annotations: new ToolAnnotations(destructiveHint: true),
    )]
    #[RequiresAdminChanges]
    public function update(
        RequestContext $context,
        ?int $id = null,
        #[Schema(format: 'uuid')]
        ?string $uid = null,
        ?string $currentHandle = null,
        ?string $name = null,
        ?string $handle = null,
        ?string $description = null,
    ): array {
        $group = $this->find($id, $uid, $currentHandle);

        if (! $group) {
            throw new ToolCallException('User group not found.');
        }

        $request = $context->getRequest();
        assert($request instanceof CallToolRequest);

        Typecast::configure($group, array_intersect_key([
            'name' => $name,
            'handle' => $handle,
            'description' => $description,
        ], $request->arguments));

        if (! $this->userGroups->saveGroup($group)) {
            throw new ToolCallException($this->validationErrors($group));
        }

        return ['group' => $this->serialize($group)];
    }

    /**
     * @param  int|null  $id  User group ID.
     * @param  string|null  $uid  User group UID.
     * @param  string|null  $handle  User group handle.
     * @return array{deleted: true}
     */
    #[McpTool(
        name: 'user-groups.delete',
        description: 'Deletes a Craft CMS user group.',
        annotations: new ToolAnnotations(destructiveHint: true),
    )]
    #[RequiresAdminChanges]
    public function delete(
        ?int $id = null,
        #[Schema(format: 'uuid')]
        ?string $uid = null,
        ?string $handle = null,
    ): array {
        $group = $this->find($id, $uid, $handle);

        if (! $group) {
            throw new ToolCallException('User group not found.');
        }

        if (! $this->userGroups->deleteGroup($group)) {
            throw new ToolCallException('User group could not be deleted.');
        }

        return ['deleted' => true];
    }

    /** @return array{groups: list<array<string, mixed>>} */
    #[McpResource(
        uri: 'craft://user-groups',
        name: 'craft-user-groups',
        title: 'Craft User Groups',
        description: 'A JSON list of Craft CMS user groups.',
        mimeType: 'application/json',
    )]
    #[RequiresAdmin]
    public function resource(): array
    {
        return $this->list();
    }

    /** @return array{group: array<string, mixed>} */
    #[McpResourceTemplate(
        uriTemplate: 'craft://user-groups/{group}',
        name: 'craft-user-groups-get',
        title: 'Craft User Group',
        description: 'A JSON Craft CMS user group record addressed by user group ID, UID, or handle.',
        mimeType: 'application/json',
    )]
    #[RequiresAdmin]
    public function resourceByIdentifier(string $group): array
    {
        $resolved = match (true) {
            ctype_digit($group) => $this->find(id: (int) $group),
            Str::isUuid($group) => $this->find(uid: $group),
            default => $this->find(handle: $group),
        };

        if (! $resolved) {
            throw new ResourceReadException('User group not found.');
        }

        return ['group' => $this->serialize($resolved)];
    }

    private function find(?int $id = null, ?string $uid = null, ?string $handle = null): ?UserGroup
    {
        return match (true) {
            $id !== null => $this->userGroups->getGroupById($id),
            $uid !== null => $this->userGroups->getGroupByUid($uid),
            $handle !== null => $this->userGroups->getGroupByHandle($handle),
            default => null,
        };
    }

    /** @return array<string, mixed> */
    private function serialize(UserGroup $group): array
    {
        $config = $group->getConfig();

        return [
            'id' => $group->id,
            'uid' => $group->uid,
            ...$config,
            'permissions' => $config['permissions'] ?? [],
        ];
    }

    private function validationErrors(UserGroup $group): string
    {
        return implode("\n", $group->errors()->all()) ?: 'User group could not be saved.';
    }
}
