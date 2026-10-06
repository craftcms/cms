<?php

declare(strict_types=1);

namespace CraftCms\Cms\Mcp\Capabilities;

use CraftCms\Cms\Mcp\Attributes\RequiresAdmin;
use CraftCms\Cms\Mcp\Attributes\RequiresAdminChanges;
use CraftCms\Cms\Site\Data\SiteGroup;
use CraftCms\Cms\Site\SiteGroups as SiteGroupService;
use CraftCms\Cms\Support\Arr;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Capability\Attribute\Schema;
use Mcp\Exception\ToolCallException;
use Mcp\Schema\ToolAnnotations;

/**
 * @since 6.0.0
 */
readonly class SiteGroups
{
    public function __construct(private SiteGroupService $siteGroups) {}

    /** @return array{count: int, groups: list<array<string, mixed>>} */
    #[McpTool(
        name: 'site-groups.list',
        description: 'Lists Craft CMS site groups.',
        annotations: new ToolAnnotations(readOnlyHint: true),
    )]
    #[RequiresAdmin]
    public function list(): array
    {
        $groups = $this->siteGroups
            ->getAllGroups()
            ->map($this->serialize(...))
            ->values();

        return [
            'count' => $groups->count(),
            'groups' => $groups->all(),
        ];
    }

    /** @return array{group: array<string, mixed>} */
    #[McpTool(
        name: 'site-groups.get',
        description: 'Gets a Craft CMS site group by ID or UID.',
        annotations: new ToolAnnotations(readOnlyHint: true),
    )]
    #[RequiresAdmin]
    public function get(
        ?int $id = null,
        #[Schema(format: 'uuid')]
        ?string $uid = null,
    ): array {
        if (count(Arr::whereNotNull([$id, $uid])) !== 1) {
            throw new ToolCallException('Provide exactly one of: id, uid.');
        }

        $group = $this->find($id, $uid);

        if (! $group) {
            throw new ToolCallException('Site group not found.');
        }

        return ['group' => $this->serialize($group)];
    }

    /** @return array{group: array<string, mixed>} */
    #[McpTool(name: 'site-groups.create', description: 'Creates a Craft CMS site group.')]
    #[RequiresAdminChanges]
    public function create(string $name): array
    {
        $group = new SiteGroup(['name' => $name]);

        if (! $this->siteGroups->saveGroup($group)) {
            throw new ToolCallException($this->validationErrors($group));
        }

        return ['group' => $this->serialize($group)];
    }

    /** @return array{group: array<string, mixed>} */
    #[McpTool(
        name: 'site-groups.update',
        description: 'Updates a Craft CMS site group.',
        annotations: new ToolAnnotations(destructiveHint: true),
    )]
    #[RequiresAdminChanges]
    public function update(
        string $name,
        ?int $id = null,
        #[Schema(format: 'uuid')]
        ?string $uid = null,
    ): array {
        $group = $this->find($id, $uid);

        if (! $group) {
            throw new ToolCallException('Site group not found.');
        }

        $group->setName($name);

        if (! $this->siteGroups->saveGroup($group)) {
            throw new ToolCallException($this->validationErrors($group));
        }

        return ['group' => $this->serialize($group)];
    }

    /** @return array{deleted: true} */
    #[McpTool(
        name: 'site-groups.delete',
        description: 'Deletes a Craft CMS site group.',
        annotations: new ToolAnnotations(destructiveHint: true),
    )]
    #[RequiresAdminChanges]
    public function delete(
        ?int $id = null,
        #[Schema(format: 'uuid')]
        ?string $uid = null,
    ): array {
        $group = $this->find($id, $uid);

        if (! $group) {
            throw new ToolCallException('Site group not found.');
        }

        if (! $this->siteGroups->deleteGroup($group)) {
            throw new ToolCallException('Site group could not be deleted.');
        }

        return ['deleted' => true];
    }

    private function find(?int $id = null, ?string $uid = null): ?SiteGroup
    {
        return match (true) {
            $id !== null => $this->siteGroups->getGroupById($id),
            $uid !== null => $this->siteGroups->getGroupByUid($uid),
            default => null,
        };
    }

    /** @return array<string, mixed> */
    private function serialize(SiteGroup $group): array
    {
        return [
            'id' => $group->id,
            'uid' => $group->uid,
            'name' => $group->getName(false),
            'siteIds' => $group->id ? $group->getSiteIds()->values()->all() : [],
        ];
    }

    private function validationErrors(SiteGroup $group): string
    {
        return implode("\n", $group->errors()->all()) ?: 'Site group could not be saved.';
    }
}
