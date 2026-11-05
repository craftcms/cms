<?php

declare(strict_types=1);

namespace craft\mcp\capabilities;

use craft\mcp\attributes\RequiresAdmin;
use craft\mcp\attributes\RequiresAdminChanges;
use craft\models\SiteGroup;
use craft\services\Sites as SiteGroupService;
use Illuminate\Support\Arr;
use Mcp\Capability\Attribute\Schema;
use Mcp\Exception\ToolCallException;

/**
 * @since 5.12.0
 */
class SiteGroups
{
    public function __construct(private SiteGroupService $siteGroups)
    {
    }

    /** @return array{count: int, groups: list<array<string, mixed>>} */
    #[RequiresAdmin]
    public function list(): array
    {
        $groups = collect($this->siteGroups->getAllGroups())
            ->map($this->serialize(...))
            ->values();

        return [
            'count' => $groups->count(),
            'groups' => $groups->all(),
        ];
    }

    /** @return array{group: array<string, mixed>} */
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

        if (!$group) {
            throw new ToolCallException('Site group not found.');
        }

        return ['group' => $this->serialize($group)];
    }

    /** @return array{group: array<string, mixed>} */
    #[RequiresAdminChanges]
    public function create(string $name): array
    {
        $group = new SiteGroup(['name' => $name]);

        if (!$this->siteGroups->saveGroup($group)) {
            throw new ToolCallException($this->validationErrors($group));
        }

        return ['group' => $this->serialize($group)];
    }

    /** @return array{group: array<string, mixed>} */
    #[RequiresAdminChanges]
    public function update(
        string $name,
        ?int $id = null,
        #[Schema(format: 'uuid')]
        ?string $uid = null,
    ): array {
        $group = $this->find($id, $uid);

        if (!$group) {
            throw new ToolCallException('Site group not found.');
        }

        $group->setName($name);

        if (!$this->siteGroups->saveGroup($group)) {
            throw new ToolCallException($this->validationErrors($group));
        }

        return ['group' => $this->serialize($group)];
    }

    /** @return array{deleted: true} */
    #[RequiresAdminChanges]
    public function delete(
        ?int $id = null,
        #[Schema(format: 'uuid')]
        ?string $uid = null,
    ): array {
        $group = $this->find($id, $uid);

        if (!$group) {
            throw new ToolCallException('Site group not found.');
        }

        if (!$this->siteGroups->deleteGroup($group)) {
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
            'siteIds' => $group->id ? $group->getSiteIds() : [],
        ];
    }

    private function validationErrors(SiteGroup $group): string
    {
        return implode("\n", $group->getErrorSummary(true)) ?: 'Site group could not be saved.';
    }
}
