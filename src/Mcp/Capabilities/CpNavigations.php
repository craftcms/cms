<?php

declare(strict_types=1);

namespace CraftCms\Cms\Mcp\Capabilities;

use CraftCms\Cms\Cp\CustomNavigations;
use CraftCms\Cms\Cp\Data\NavNode;
use CraftCms\Cms\Cp\NavCustomizer;
use CraftCms\Cms\Cp\Navigation;
use CraftCms\Cms\Edition;
use CraftCms\Cms\Http\Requests\NavCustomizationRequest;
use CraftCms\Cms\Mcp\Attributes\RequiresAdmin;
use CraftCms\Cms\Mcp\Attributes\RequiresAdminChanges;
use CraftCms\Cms\Mcp\EditionFeatures;
use CraftCms\Cms\User\Data\UserGroup;
use CraftCms\Cms\User\UserGroups;
use Illuminate\Support\Facades\Validator;
use Mcp\Capability\Attribute\Schema;
use Mcp\Exception\ToolCallException;

/**
 * The control panel's navigations: the default one, and those for user groups.
 *
 * Navigations are read and written as the tree the navigation builder edits.
 * The default navigation only keeps how it differs from the nav Craft and
 * plugins build, and a user group's only how it differs from the default
 * navigation, so items added later still turn up.
 *
 * @phpstan-import-type GroupNavigation from CustomNavigations
 *
 * @since 6.0.0
 */
readonly class CpNavigations
{
    /** The lookup value for the default navigation. */
    public const string DEFAULT = 'default';

    private const array UserGroupSchema = [
        'type' => 'string',
        'minLength' => 1,
        'description' => 'A user group’s handle or UID, or `default` for the default navigation.',
    ];

    private const array TreeSchema = [
        'type' => 'array',
        'maxItems' => 500,
        'description' => 'The whole navigation as configuration.get returns it, edited: sections and items in order, each with their `children`. Reorder nodes, move items between the root and sections, change `label` or `icon`, set `hidden`, or add sections and links with `source: custom`, a new `id` of `custom-` followed by lowercase letters and digits (such as `custom-7f3a`), and `type: section` or `type: link` (with `url`). Nodes left out are hidden.',
        'items' => [
            'type' => 'object',
            'properties' => [
                'id' => ['type' => 'string', 'minLength' => 1],
                'kind' => ['type' => 'string', 'enum' => ['section', 'item', 'child']],
                'source' => ['type' => 'string', 'enum' => ['default', 'custom']],
                'type' => ['type' => ['string', 'null'], 'enum' => ['section', 'link', null]],
                'label' => ['type' => 'string'],
                'icon' => ['type' => ['string', 'null']],
                'url' => ['type' => ['string', 'null'], 'maxLength' => 2048],
                'newWindow' => ['type' => 'boolean'],
                'hidden' => ['type' => 'boolean'],
                'children' => ['type' => 'array', 'items' => ['type' => 'object', 'additionalProperties' => true]],
            ],
            'required' => ['id', 'kind'],
            'additionalProperties' => true,
        ],
    ];

    private const array PositionSchema = [
        'type' => 'integer',
        'minimum' => 1,
        'description' => 'Where the user group’s navigation goes among the others, from 1. A user in several groups gets the first navigation of theirs.',
    ];

    public function __construct(
        private CustomNavigations $navigations,
        private NavCustomizer $customizer,
        private Navigation $navigation,
        private UserGroups $userGroups,
    ) {}

    /** @return array{count: int, navigations: list<array<string, mixed>>} */
    #[RequiresAdmin]
    public function list(): array
    {
        $navigations = [
            ...$this->groupNavigationsAvailable()
                ? $this->navigations->getGroupNavigations()
                    ->map(fn (array $navigation, int $i): array => $this->serialize($navigation['userGroup'], $navigation['nav'], $i + 1))
                    ->all()
                : [],
            $this->serialize(null, $this->navigations->getDefault()),
        ];

        return ['count' => count($navigations), 'navigations' => $navigations];
    }

    /** @return array{navigation: array<string, mixed>} */
    #[RequiresAdmin]
    public function get(
        #[Schema(definition: self::UserGroupSchema)]
        string $userGroup,
    ): array {
        $group = $this->group($userGroup);

        if ($group === null) {
            return ['navigation' => $this->serialize(null, $this->navigations->getDefault(), withTree: true)];
        }

        $navigation = $this->groupNavigation($group) ?? throw new ToolCallException("The “{$group->name}” user group doesn’t have a navigation.");

        return ['navigation' => $this->serialize($group, $navigation['nav'], $this->position($group), withTree: true)];
    }

    /**
     * @param  list<array<string, mixed>>|null  $tree
     * @return array{navigation: array<string, mixed>}
     */
    #[RequiresAdminChanges]
    public function create(
        #[Schema(definition: self::UserGroupSchema)]
        string $userGroup,
        #[Schema(definition: self::TreeSchema)]
        ?array $tree = null,
        #[Schema(definition: self::PositionSchema)]
        ?int $position = null,
    ): array {
        $group = $this->group($userGroup) ?? throw new ToolCallException('The default navigation always exists. Update it instead.');

        if ($this->groupNavigation($group) !== null) {
            throw new ToolCallException("The “{$group->name}” user group already has a navigation. Update it instead.");
        }

        $this->navigations->saveGroupNavigation($group, $tree === null ? [] : $this->diff($group, $tree));
        $this->move($group, $position);

        return $this->get((string) $group->uid);
    }

    /**
     * @param  list<array<string, mixed>>|null  $tree
     * @return array{navigation: array<string, mixed>}
     */
    #[RequiresAdminChanges]
    public function update(
        #[Schema(definition: self::UserGroupSchema)]
        string $userGroup,
        #[Schema(definition: self::TreeSchema)]
        ?array $tree = null,
        #[Schema(definition: self::PositionSchema)]
        ?int $position = null,
    ): array {
        $group = $this->group($userGroup);

        if ($group === null) {
            if ($position !== null) {
                throw new ToolCallException('The default navigation always comes last, so it doesn’t have a position.');
            }

            if ($tree !== null) {
                $this->navigations->saveDefault($this->diff(null, $tree));
            }

            return $this->get(self::DEFAULT);
        }

        if ($this->groupNavigation($group) === null) {
            throw new ToolCallException("The “{$group->name}” user group doesn’t have a navigation. Create it instead.");
        }

        if ($tree !== null) {
            $this->navigations->saveGroupNavigation($group, $this->diff($group, $tree));
        }

        $this->move($group, $position);

        return $this->get((string) $group->uid);
    }

    /** @return array{deleted: true} */
    #[RequiresAdminChanges]
    public function delete(
        #[Schema(definition: self::UserGroupSchema)]
        string $userGroup,
    ): array {
        $group = $this->group($userGroup);

        if ($group === null) {
            $this->navigations->saveDefault([]);

            return ['deleted' => true];
        }

        if ($this->groupNavigation($group) === null) {
            throw new ToolCallException("The “{$group->name}” user group doesn’t have a navigation.");
        }

        $this->navigations->deleteGroupNavigation((string) $group->uid);

        return ['deleted' => true];
    }

    /** The user group a lookup names, or null for the default navigation. */
    private function group(string $lookup): ?UserGroup
    {
        if ($lookup === self::DEFAULT) {
            return null;
        }

        if (! $this->groupNavigationsAvailable()) {
            $required = EditionFeatures::name(Edition::Pro);
            $current = EditionFeatures::name(Edition::get());

            throw new ToolCallException("User group navigations require $required. This site runs $current.");
        }

        return $this->userGroups->getGroupByUid($lookup)
            ?? $this->userGroups->getGroupByHandle($lookup)
            ?? throw new ToolCallException("No user group has the handle or UID “{$lookup}”.");
    }

    private function groupNavigationsAvailable(): bool
    {
        return Edition::isAtLeast(Edition::Pro);
    }

    /** @return GroupNavigation|null */
    private function groupNavigation(UserGroup $group): ?array
    {
        return $this->navigations->getGroupNavigation((string) $group->uid);
    }

    private function position(UserGroup $group): ?int
    {
        $index = $this->navigations->getGroupNavigations()
            ->search(fn (array $navigation): bool => $navigation['userGroup']->uid === $group->uid);

        return $index === false ? null : $index + 1;
    }

    private function move(UserGroup $group, ?int $position): void
    {
        if ($position === null) {
            return;
        }

        $uids = $this->navigations->getGroupNavigations()
            ->map(fn (array $navigation): string => (string) $navigation['userGroup']->uid)
            ->reject(fn (string $uid): bool => $uid === $group->uid)
            ->values()
            ->all();

        array_splice($uids, min($position, count($uids) + 1) - 1, 0, [(string) $group->uid]);
        $this->navigations->reorderGroupNavigations($uids);
    }

    /**
     * The customization an edited tree amounts to, against the nav the group
     * (or everyone, for the default navigation) starts from.
     *
     * @param  list<array<string, mixed>>  $tree
     * @return array<mixed>
     */
    private function diff(?UserGroup $group, array $tree): array
    {
        $validator = Validator::make(['nav' => $tree], new NavCustomizationRequest()->rules());

        if ($validator->fails()) {
            throw new ToolCallException(implode("\n", $validator->errors()->all()));
        }

        $this->assertCustomIds($tree);

        return $this->customizer->diff($this->baseTree($group), array_values($tree), $this->layeredOver($group));
    }

    /**
     * Refuses custom sections and links whose ids the customizer would drop,
     * rather than saving the navigation without them.
     *
     * @param  list<array<string, mixed>>  $nodes
     */
    private function assertCustomIds(array $nodes): void
    {
        foreach ($nodes as $node) {
            $id = (string) ($node['id'] ?? '');

            if (($node['source'] ?? null) === 'custom' && ! preg_match(NavCustomizer::CUSTOM_ID_PATTERN, $id)) {
                throw new ToolCallException("The custom node “{$id}” needs an id of `custom-` followed by lowercase letters and digits, such as `custom-7f3a`.");
            }

            if (is_array($node['children'] ?? null)) {
                $this->assertCustomIds($node['children']);
            }
        }
    }

    /**
     * The customization a navigation builds on: the default navigation's, for
     * a user group's, and none for the default navigation itself.
     *
     * @return array<mixed>
     */
    private function layeredOver(?UserGroup $group): array
    {
        return $group === null ? [] : $this->navigations->getDefault();
    }

    /** @return array<mixed> */
    private function baseTree(?UserGroup $group): array
    {
        return $group === null
            ? $this->navigation->getBaseTree()
            : $this->navigation->getBaseTreeForGroup($group);
    }

    /**
     * @param  array<mixed>  $nav
     * @return array<string, mixed>
     */
    private function serialize(?UserGroup $group, array $nav, ?int $position = null, bool $withTree = false): array
    {
        return [
            'userGroup' => $group === null ? self::DEFAULT : $group->handle,
            'userGroupUid' => $group?->uid,
            'name' => $group?->name,
            ...$group === null ? [] : ['position' => $position],
            'changes' => $this->customizer->summarize($nav),
            ...$withTree ? ['tree' => array_map(
                fn (NavNode $node): array => $this->node($node->toArray()),
                $this->customizer->resolve($this->baseTree($group), $nav, $this->layeredOver($group)),
            )] : [],
        ];
    }

    /**
     * A node without the SVG markup the builder draws icons from.
     *
     * @param  array<string, mixed>  $node
     * @return array<string, mixed>
     */
    private function node(array $node): array
    {
        unset($node['iconSvg']);
        $node['children'] = array_map($this->node(...), $node['children'] ?? []);

        return $node;
    }
}
