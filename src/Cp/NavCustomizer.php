<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp;

use CraftCms\Cms\Condition\Conditions;
use CraftCms\Cms\Cp\Data\NavItem;
use CraftCms\Cms\Cp\Data\NavNode;
use CraftCms\Cms\Http\Requests\NavCustomizationRequest;
use CraftCms\Cms\Support\Json;
use CraftCms\Cms\Support\Url;
use CraftCms\Cms\User\Conditions\UserCondition;
use CraftCms\Cms\User\Contracts\CraftUser;
use Illuminate\Support\Facades\Validator;
use InvalidArgumentException;

use function CraftCms\Cms\t;

/**
 * Applies a nav customization over the nav Craft and plugins build, and
 * works out the customization a builder's edits amount to.
 *
 * A customization is never a copy of the nav. It's keyed by item id and holds
 * only what changed, so items a plugin adds later turn up on their own, and
 * entries for items that have gone away are skipped:
 *
 * ```php
 * [
 *     // Per parent, only where the order changed. Unlisted items follow in
 *     // their default order.
 *     'order' => [
 *         'root' => ['nav-dashboard', 'custom-7f3a', 'nav-group-content'],
 *         'nav-group-content' => ['nav-assets', 'nav-content-entries'],
 *     ],
 *     'items' => [
 *         'nav-content-entries' => ['label' => 'Articles', 'icon' => 'newspaper'],
 *         'nav-commerce' => ['parent' => 'nav-group-content'],
 *         'nav-graphql' => ['hidden' => true],
 *         'nav-users-admins' => ['parent' => 'root'],
 *         'custom-7f3a' => ['type' => 'section', 'label' => 'Shop'],
 *         'custom-c0d4' => ['type' => 'link', 'label' => 'Docs', 'url' => 'https://…', 'parent' => 'custom-7f3a'],
 *     ],
 * ]
 * ```
 *
 * The nav it works on has three levels: sections (top-level groups) and
 * items at the root, items in sections, and an item's children. Sections
 * don't nest. Items move between the root and sections. Anything beneath an
 * item — its children, and the members of a heading among them — is
 * reordered among its siblings, or moved out to the root or a section, taking
 * its own children along. It can go back beneath the item it came from, but
 * never beneath another one.
 *
 * A user group's customization is layered over the default one with
 * {@see layer()}: it holds only what the group changes from the default,
 * including undoing it, such as `'hidden' => false` for something the default
 * hides.
 *
 * Hiding is cosmetic: a customization is applied to the nav the user may
 * already see, so it can leave things out but never add access. Sections and
 * links it adds can have a user condition, which applies to admins too.
 *
 * @phpstan-type NavDiff array{order?: array<string, list<string>>, items?: array<string, array<string, mixed>>}
 *
 * @since 6.0.0
 */
class NavCustomizer
{
    /** The parent that the top level of the nav goes by. */
    public const string ROOT = 'root';

    /** What the ids of sections and links the builder adds look like. */
    public const string CUSTOM_ID_PATTERN = '/^custom-[a-z0-9]+$/';

    /**
     * The nav with a customization applied, for a user. Hidden items are left
     * out, and so are sections and links whose user condition the user doesn't
     * match.
     *
     * @param  NavItem[]  $tree
     * @param  NavDiff  $diff
     * @return NavItem[]
     */
    public function apply(array $tree, array $diff, ?CraftUser $user): array
    {
        if ($diff === []) {
            return $tree;
        }

        return $this->render($this->resolve($tree, $diff), $user);
    }

    /**
     * A customization layered over another: each item's settings, and each
     * parent's order, replace the base's where it has them.
     *
     * @param  NavDiff  $base
     * @param  NavDiff  $diff
     * @return NavDiff
     */
    public static function layer(array $base, array $diff): array
    {
        $items = $base['items'] ?? [];

        foreach ($diff['items'] ?? [] as $id => $config) {
            $items[$id] = array_replace($items[$id] ?? [], $config);
        }

        return array_filter([
            'order' => array_replace($base['order'] ?? [], $diff['order'] ?? []),
            'items' => $items,
        ]);
    }

    /**
     * The nav with a customization applied, as the builder edits it: hidden
     * items kept and flagged, and each item's defaults alongside its overrides.
     * A group's customization is resolved over `$base`, the default one, whose
     * sections and links are marked as inherited.
     *
     * @param  NavItem[]  $tree
     * @param  NavDiff  $diff
     * @param  NavDiff  $base
     * @return list<NavNode>
     */
    public function resolve(array $tree, array $diff, array $base = []): array
    {
        $layered = self::layer($base, $diff);
        $items = $layered['items'] ?? [];

        $nodes = $this->collectNodes($tree);
        $this->addCreated($nodes, $items, $base['items'] ?? []);
        $this->liftMoved($nodes, $items);

        return $this->place($nodes, $items, $layered['order'] ?? []);
    }

    /**
     * A short summary of what a customization changes, such as “2 added, 3 hidden”.
     *
     * @param  NavDiff  $customization
     */
    public function summarize(array $customization): string
    {
        $counts = collect($customization['items'] ?? [])
            ->map(fn (array $item) => match (true) {
                isset($item['type']) => 'added',
                ! empty($item['hidden']) => 'hidden',
                isset($item['hidden']) => 'shown',
                isset($item['label']) || isset($item['icon']) => 'edited',
                isset($item['parent']) => 'moved',
                default => null,
            })
            ->filter()
            ->countBy();

        $parts = array_filter([
            $counts->has('added') ? t('{num, number} added', ['num' => $counts['added']]) : null,
            $counts->has('hidden') ? t('{num, number} hidden', ['num' => $counts['hidden']]) : null,
            $counts->has('shown') ? t('{num, number} shown', ['num' => $counts['shown']]) : null,
            $counts->has('edited') ? t('{num, number} edited', ['num' => $counts['edited']]) : null,
            $counts->has('moved') ? t('{num, number} moved', ['num' => $counts['moved']]) : null,
        ]);

        if ($parts !== []) {
            return implode(', ', $parts);
        }

        return empty($customization['order']) ? t('No changes') : t('Reordered');
    }

    /**
     * The customization that turns the nav into the builder's edited tree.
     *
     * The edited tree is read as is, then put through {@see resolve()}, which
     * decides what may go where. Only what still differs from the nav is kept,
     * so anything the nav no longer has, such as an item since removed, is
     * pruned on save. For a group, `$base` is the default customization, and
     * only what differs from it is kept.
     *
     * @param  NavItem[]  $tree
     * @param  list<array<string, mixed>>  $edited  The tree as {@see resolve()} gives it, edited and posted back
     * @param  NavDiff  $base
     * @return NavDiff
     */
    public function diff(array $tree, array $edited, array $base = []): array
    {
        $posted = ['order' => [], 'items' => []];
        $this->readEdited($edited, self::ROOT, $posted);

        $resolved = $this->resolve($tree, $posted);
        $items = $this->subtract($this->changes($resolved), $base['items'] ?? [], $this->nodesById($resolved));
        $currentOrder = $this->childOrder($this->resolve($tree, ['items' => $items], $base));

        $order = collect($posted['order'])
            ->intersectByKeys($currentOrder)
            ->map(fn (array $ids, string $parent) => collect($ids)->intersect($currentOrder[$parent])->unique()->values()->all())
            // Kept where it differs, and always where the customization
            // creates items: project config may hand `items` back in another
            // order.
            ->filter(fn (array $ids, string $parent) => $ids !== array_values(array_intersect($currentOrder[$parent], $ids))
                || collect($ids)->contains(fn (string $id) => isset($items[$id]['type'])))
            ->all();

        return array_filter(['order' => $order, 'items' => $items]);
    }

    /**
     * What a customization changes from a base one. Where the base changes
     * something this one doesn't, it's put back explicitly, so that layered
     * over the base, it comes out as edited.
     *
     * @param  array<string, array<string, mixed>>  $items
     * @param  array<string, array<string, mixed>>  $base
     * @param  array<string, NavNode>  $nodes  The nodes the edited tree has, by id
     * @return array<string, array<string, mixed>>
     */
    private function subtract(array $items, array $base, array $nodes): array
    {
        $changes = [];

        foreach (array_keys($items + $base) as $id) {
            $node = $nodes[$id] ?? null;

            if ($node === null) {
                continue;
            }

            $own = $items[$id] ?? [];
            $inherited = $base[$id] ?? [];
            $config = array_filter(
                $own,
                fn (mixed $value, string $key) => ! array_key_exists($key, $inherited) || Json::encode($inherited[$key]) !== Json::encode($value),
                ARRAY_FILTER_USE_BOTH,
            );

            // An empty label or icon is no override, so the nav's own shows.
            foreach (array_keys(array_diff_key($inherited, $own)) as $key) {
                $config[$key] = match ($key) {
                    'parent' => $node->defaultParent,
                    'hidden', 'newWindow', 'userCondition' => false,
                    default => '',
                };
            }

            if ($config !== []) {
                $changes[$id] = $config;
            }
        }

        return $changes;
    }

    /**
     * @param  list<NavNode>  $nodes
     * @return array<string, NavNode>
     */
    private function nodesById(array $nodes): array
    {
        $byId = [];

        foreach ($nodes as $node) {
            $byId[$node->id] = $node;
            $byId = [...$byId, ...$this->nodesById($node->children)];
        }

        return $byId;
    }

    /**
     * Reads the edited tree as a customization that lists everything: each
     * node's place, order and settings, whether or not they changed.
     *
     * @param  array<mixed>  $entries
     * @param  array{order: array<string, list<string>>, items: array<string, array<string, mixed>>}  $posted
     */
    private function readEdited(array $entries, string $parent, array &$posted): void
    {
        foreach ($entries as $entry) {
            $id = is_array($entry) ? (string) ($entry['id'] ?? '') : '';

            if ($id === '') {
                continue;
            }

            $posted['order'][$parent][] = $id;
            $posted['items'][$id] = [
                ...array_filter(
                    array_intersect_key($entry, array_flip(['type', 'label', 'icon', 'url', 'newWindow', 'hidden'])),
                    is_scalar(...),
                ),
                ...(is_array($entry['userCondition'] ?? null) ? ['userCondition' => $entry['userCondition']] : []),
                'parent' => $parent,
            ];

            $this->readEdited(is_array($entry['children'] ?? null) ? $entry['children'] : [], $id, $posted);
        }
    }

    /**
     * What each node changes from the nav: its settings, and for items, where
     * they moved to. Created sections and links are kept whole.
     *
     * @param  list<NavNode>  $nodes
     * @return array<string, array<string, mixed>>
     */
    private function changes(array $nodes): array
    {
        return collect($nodes)->reduce(function (array $items, NavNode $node) {
            $config = array_filter($node->isCustom() ? [
                'type' => $node->type,
                'label' => $node->label,
                'url' => $node->url,
                // Links without an icon of their own get the link icon.
                'icon' => $node->type === 'link' && $node->icon !== 'link' ? $node->icon : null,
                'newWindow' => $node->newWindow ?: null,
                'userCondition' => $this->conditionConfig($node->userCondition),
                'hidden' => $node->hidden ?: null,
            ] : [
                'label' => $node->label !== $node->defaultLabel ? $node->label : null,
                'icon' => $node->icon !== $node->defaultIcon ? $node->icon : null,
                'hidden' => $node->hidden ?: null,
            ]);

            if ($node->kind === 'item' && $node->parent !== $node->defaultParent) {
                $config['parent'] = $node->parent;
            }

            return [
                ...$items,
                ...($config !== [] ? [$node->id => $config] : []),
                ...$this->changes($node->children),
            ];
        }, []);
    }

    /**
     * A label that overrides the default, if there is one.
     *
     * @param  array<string, mixed>  $entry
     */
    private function label(array $entry): ?string
    {
        $label = (string) ($entry['label'] ?? '');

        return $label === '' ? null : $label;
    }

    /**
     * The nav's sections and the items at the root and in sections, by id.
     * What's beneath an item stays with it.
     *
     * @param  NavItem[]  $tree
     * @return array<string, NavNode>
     */
    private function collectNodes(array $tree): array
    {
        $nodes = [];

        foreach ($tree as $item) {
            if ($item->group) {
                $nodes[(string) $item->id] = NavNode::fromItem($item, 'section', self::ROOT);

                foreach (is_array($item->subnav) ? $item->subnav : [] as $child) {
                    $nodes[(string) $child->id] ??= NavNode::fromItem($child, 'item', (string) $item->id);
                }

                continue;
            }

            $nodes[(string) $item->id] ??= NavNode::fromItem($item, 'item', self::ROOT);
        }

        return $nodes;
    }

    /**
     * Adds the sections and links the customization creates, marking those
     * the base customization creates as inherited.
     *
     * @param  array<string, NavNode>  $nodes
     * @param  array<string, array<string, mixed>>  $items
     * @param  array<string, array<string, mixed>>  $baseItems
     */
    private function addCreated(array &$nodes, array $items, array $baseItems): void
    {
        foreach ($items as $id => $config) {
            // The id becomes a project config key, so it's held to what the
            // builder gives new items.
            if (isset($nodes[$id]) || ! isset($config['type']) || ! preg_match(self::CUSTOM_ID_PATTERN, (string) $id)) {
                continue;
            }

            $node = $this->customNode($id, $config);

            if ($node !== null) {
                $node->inherited = isset($baseItems[$id]['type']);
                $nodes[$id] = $node;
            }
        }
    }

    /**
     * Takes what's moved out from beneath its item, and adds it to the items
     * at the root and in sections.
     *
     * @param  array<string, NavNode>  $nodes
     * @param  array<string, array<string, mixed>>  $items
     */
    private function liftMoved(array &$nodes, array $items): void
    {
        foreach ($items as $id => $config) {
            $parent = $config['parent'] ?? null;

            if (
                $parent === null
                || isset($nodes[$id])
                || ($parent !== self::ROOT && ($nodes[$parent]->kind ?? null) !== 'section')
            ) {
                continue;
            }

            foreach ($nodes as $node) {
                $lifted = $node->kind === 'item' ? $node->detach($id) : null;

                if ($lifted !== null) {
                    $lifted->kind = 'item';
                    $nodes[$id] = $lifted;
                    break;
                }
            }
        }
    }

    /**
     * Works the overrides in, puts each item under the parent the
     * customization gives it, and orders everything.
     *
     * @param  array<string, NavNode>  $nodes
     * @param  array<string, array<string, mixed>>  $items
     * @param  array<string, list<string>>  $order
     * @return list<NavNode>
     */
    private function place(array $nodes, array $items, array $order): array
    {
        $byParent = [self::ROOT => []];
        $moved = [];

        foreach ($nodes as $id => $node) {
            $config = $items[$id] ?? [];
            $this->applyOverrides($node, $config);

            if ($node->kind === 'item') {
                $parent = $config['parent'] ?? $node->defaultParent;
                $isValidParent = $parent === self::ROOT || ($nodes[$parent]->kind ?? null) === 'section';
                $node->parent = $isValidParent ? $parent : $node->defaultParent;
                $this->resolveChildren($node, $items, $order);
            }

            if ($node->parent === $node->defaultParent) {
                $byParent[$node->parent][] = $node;
            } else {
                $moved[] = $node;
            }
        }

        // Moved in after the items already there, so a parent's own items
        // keep their places.
        foreach ($moved as $node) {
            $byParent[$node->parent][] = $node;
        }

        $root = $this->ordered($byParent[self::ROOT], $order[self::ROOT] ?? []);

        foreach ($root as $node) {
            if ($node->kind === 'section') {
                $node->children = $this->ordered($byParent[$node->id] ?? [], $order[$node->id] ?? []);
            }
        }

        return $root;
    }

    /**
     * Applies overrides and order beneath a node, however deep.
     *
     * @param  array<string, array<string, mixed>>  $items
     * @param  array<string, list<string>>  $order
     */
    private function resolveChildren(NavNode $node, array $items, array $order): void
    {
        $node->children = $this->ordered($node->children, $order[$node->id] ?? []);

        foreach ($node->children as $child) {
            $this->applyOverrides($child, $items[$child->id] ?? []);
            $this->resolveChildren($child, $items, $order);
        }
    }

    /**
     * A section or link the customization adds.
     *
     * @param  array<string, mixed>  $config
     */
    private function customNode(string $id, array $config): ?NavNode
    {
        $label = $this->label($config);

        if ($label === null) {
            return null;
        }

        $type = $config['type'] ?? null;

        $userCondition = is_array($config['userCondition'] ?? null) && isset($config['userCondition']['class'])
            ? $config['userCondition']
            : null;

        if ($type === 'section') {
            $node = $this->markCustom(
                NavNode::fromItem(new NavItem()->id($id)->label($label)->group(true)->subnav([]), 'section', self::ROOT),
                'section',
            );
            $node->userCondition = $userCondition;

            return $node;
        }

        $url = (string) ($config['url'] ?? '');

        // Checked here as well as in the request, which only reaches so deep.
        if ($type !== 'link' || Validator::make(['url' => $url], ['url' => ['required', ...NavCustomizationRequest::linkRules()]])->fails()) {
            return null;
        }

        $newWindow = ! empty($config['newWindow']);
        $isAbsolute = Url::isAbsoluteUrl($url);
        $item = new NavItem()
            ->id($id)
            ->label($label)
            ->href($isAbsolute ? $url : Url::cpUrl($url))
            ->icon(($config['icon'] ?? '') ?: 'link')
            ->external($newWindow || ($isAbsolute && ! str_starts_with($url, Url::baseCpUrl())));

        if ($newWindow) {
            $item->linkAttributes(['target' => '_blank', 'rel' => 'noopener']);
        }

        $node = $this->markCustom(NavNode::fromItem($item, 'item', self::ROOT), 'link');
        $node->url = $url;
        $node->newWindow = $newWindow;
        $node->userCondition = $userCondition;

        return $node;
    }

    /**
     * A user condition's config as it's saved, or null if it has no rules.
     * One that can't be read is kept as it is, so it matches nobody rather
     * than everybody.
     *
     * @param  array<string, mixed>|null  $config
     * @return array<string, mixed>|null
     */
    private function conditionConfig(?array $config): ?array
    {
        if ($config === null) {
            return null;
        }

        try {
            $condition = app(Conditions::class)->createCondition($config);
        } catch (InvalidArgumentException) {
            return $config;
        }

        return $condition->getConditionRules()->getRules() === [] ? null : $condition->getConfig();
    }

    /** Whether a user gets to see a node, by its user condition. */
    private function isVisibleTo(NavNode $node, ?CraftUser $user): bool
    {
        if ($node->userCondition === null) {
            return true;
        }

        if ($user === null) {
            return false;
        }

        try {
            $condition = app(Conditions::class)->createCondition($node->userCondition);
        } catch (InvalidArgumentException) {
            return false;
        }

        return $condition instanceof UserCondition && $condition->matchElement($user->asElement());
    }

    /**
     * @param  'section'|'link'  $type
     */
    private function markCustom(NavNode $node, string $type): NavNode
    {
        $node->source = 'custom';
        $node->type = $type;
        $node->defaultLabel = null;
        $node->defaultIcon = null;

        return $node;
    }

    /** @param  array<string, mixed>  $config */
    private function applyOverrides(NavNode $node, array $config): void
    {
        $label = $this->label($config);

        if ($label !== null) {
            $node->label = $label;
        }

        $icon = $config['icon'] ?? null;

        if ($icon !== null && $icon !== '' && $node->kind !== 'child') {
            $node->icon = $icon;
            $node->iconSvg = null;
        }

        $node->hidden = ! empty($config['hidden']);
    }

    /**
     * Nodes in the customized order: those it lists first, then the rest in
     * their default order.
     *
     * @param  list<NavNode>  $nodes
     * @param  list<string>  $order
     * @return list<NavNode>
     */
    private function ordered(array $nodes, array $order): array
    {
        $positions = array_flip($order);

        return collect($nodes)
            ->sortBy(fn (NavNode $node, int $i) => $positions[$node->id] ?? count($positions) + $i)
            ->values()
            ->all();
    }

    /**
     * Each parent's children ids, in order.
     *
     * @param  list<NavNode>  $nodes
     * @return array<string, list<string>>
     */
    private function childOrder(array $nodes): array
    {
        $order = [self::ROOT => array_column($nodes, 'id')];
        $walk = function (array $nodes) use (&$walk, &$order): void {
            foreach ($nodes as $node) {
                $order[$node->id] = array_column($node->children, 'id');
                $walk($node->children);
            }
        };
        $walk($nodes);

        return $order;
    }

    /**
     * @param  list<NavNode>  $nodes
     * @return NavItem[]
     */
    private function render(array $nodes, ?CraftUser $user): array
    {
        $items = [];

        foreach ($nodes as $node) {
            if ($node->hidden || ! $this->isVisibleTo($node, $user)) {
                continue;
            }

            $item = $node->item;
            $item->label = $node->label;
            $item->icon = $node->icon;
            $item->iconSvg = $node->iconSvg;

            // A renamed item can still be searched for by its old name.
            if ($node->defaultLabel !== null && $node->label !== $node->defaultLabel) {
                $item->keywords = trim(($item->keywords ?? '').' '.$node->defaultLabel);
            }

            if ($node->kind === 'section') {
                $children = $this->render($node->children, $user);

                // Moved out of, or hidden from, until nothing's left.
                if ($children === []) {
                    continue;
                }

                $item->subnav = $children;
            } elseif (is_array($item->subnav) && $item->subnav !== []) {
                // Rebuilt from the nodes, since some may have moved out.
                $children = $this->render($node->children, $user);

                // A heading within a subnav, with nothing left beneath it.
                if ($children === [] && $item->group) {
                    continue;
                }

                $item->subnav = $children === [] ? false : $children;
            }

            $items[] = $item;
        }

        return $items;
    }
}
