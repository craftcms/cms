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
        'description' => 'The whole navigation as configuration.get returns it, edited: sections and items in order, each with their `children`. Reorder nodes, move items between the root and sections, change `label` or `icon`, set `hidden` to hide a node, or add sections and links with `source: custom`, a new `id` of `custom-` followed by lowercase letters and digits (such as `custom-7f3a`), a `label`, and `type: section` or `type: link` (with `url`). Custom sections and links can have a `userCondition`, the config of a user condition, so only users who match it see them. Pass every node the current tree has; drop a custom node to remove it.',
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
                'userCondition' => ['type' => ['object', 'null'], 'additionalProperties' => true],
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

        $navigation = $this->groupNavigation($group) ?? throw new ToolCallException("The “{$group->name}” user group doesn’t have a navigation. Create one to customize what the group sees; until then, it gets the default navigation.");

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

        $this->navigations->saveGroupNavigation($group, $tree === null ? [] : $this->diff($group, $tree, []));
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
                $this->navigations->saveDefault($this->diff(null, $tree, $this->navigations->getDefault()));
            }

            return $this->get(self::DEFAULT);
        }

        if ($this->groupNavigation($group) === null) {
            throw new ToolCallException("The “{$group->name}” user group doesn’t have a navigation. Create it instead.");
        }

        if ($tree !== null) {
            $this->navigations->saveGroupNavigation($group, $this->diff($group, $tree, $this->groupNavigation($group)['nav']));
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
     * Every node the navigation has must be in the tree, since a node left out
     * would otherwise go back to how the nav it builds on has it. And every
     * node in the tree must make it into the navigation, rather than being
     * dropped quietly.
     *
     * @param  list<array<string, mixed>>  $tree
     * @param  array<mixed>  $current  The navigation's customization now
     * @return array<mixed>
     */
    private function diff(?UserGroup $group, array $tree, array $current): array
    {
        $validator = Validator::make(['nav' => $tree], new NavCustomizationRequest()->rules());

        if ($validator->fails()) {
            throw new ToolCallException(implode("\n", $validator->errors()->all()));
        }

        $posted = $this->postedIds($tree);
        $missing = array_diff($this->resolvedIds($group, $current, custom: false), $posted);

        if ($missing !== []) {
            throw new ToolCallException('The tree leaves out '.$this->idList($missing).'. Pass the whole tree, with `hidden: true` on nodes to hide.');
        }

        $diff = $this->customizer->diff($this->baseTree($group), array_values($tree), $this->layeredOver($group));
        $dropped = array_diff($posted, $this->resolvedIds($group, $diff));

        if ($dropped !== []) {
            throw new ToolCallException('The navigation can’t have '.$this->idList($dropped).'. Nodes must be ones the current tree has, or new sections and links with `source: custom`, an `id` of `custom-` followed by lowercase letters and digits (such as `custom-7f3a`), a `label`, and `type: section` or `type: link` (with `url`). Items can sit at the root or in sections, and sections only at the root.');
        }

        return $diff;
    }

    /**
     * The ids of the nodes in a posted tree.
     *
     * @param  array<mixed>  $nodes
     * @return list<string>
     */
    private function postedIds(array $nodes): array
    {
        $ids = [];

        foreach ($nodes as $node) {
            if (! is_array($node)) {
                continue;
            }

            $ids[] = (string) ($node['id'] ?? '');
            array_push($ids, ...$this->postedIds(is_array($node['children'] ?? null) ? $node['children'] : []));
        }

        return $ids;
    }

    /**
     * The ids of the nodes a navigation has, optionally leaving out the
     * custom ones, which may be removed.
     *
     * @param  array<mixed>  $nav
     * @return list<string>
     */
    private function resolvedIds(?UserGroup $group, array $nav, bool $custom = true): array
    {
        $ids = [];
        $collect = function (array $nodes) use (&$collect, &$ids, $custom): void {
            foreach ($nodes as $node) {
                /** @var NavNode $node */
                if ($custom || ! $node->isCustom() || $node->inherited) {
                    $ids[] = $node->id;
                }

                $collect($node->children);
            }
        };
        $collect($this->resolve($group, $nav));

        return $ids;
    }

    /**
     * The first few ids, then how many more there are.
     *
     * @param  array<string>  $ids
     */
    private function idList(array $ids): string
    {
        $ids = array_values(array_unique($ids));
        $list = implode(', ', array_map(fn (string $id): string => "`$id`", array_slice($ids, 0, 10)));

        return count($ids) > 10 ? "$list and ".(count($ids) - 10).' more' : $list;
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

    /**
     * @param  array<mixed>  $nav
     * @return list<NavNode>
     */
    private function resolve(?UserGroup $group, array $nav): array
    {
        return $this->customizer->resolve($this->baseTree($group), $nav, $this->layeredOver($group));
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
                $this->resolve($group, $nav),
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
