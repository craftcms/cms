<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\Data;

use CraftCms\Cms\Cp\NavCustomizer;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Arr;
use JsonSerializable;
use Spatie\TypeScriptTransformer\Attributes\Hidden;

/**
 * One node of a customized nav: what {@see NavCustomizer}
 * works on, and what the nav builder edits and posts back.
 *
 * @implements Arrayable<string, mixed>
 *
 * @since 6.0.0
 */
class NavNode implements Arrayable, JsonSerializable
{
    /** @var 'default'|'custom' Where it comes from: Craft or a plugin, or the builder. */
    public string $source = 'default';

    /** @var 'section'|'link'|null For custom nodes: a section or a link. */
    public ?string $type = null;

    public string $label;

    public ?string $defaultLabel = null;

    public ?string $icon = null;

    public ?string $defaultIcon = null;

    public ?string $iconSvg = null;

    public ?string $href = null;

    /** For custom links: where it goes. */
    public ?string $url = null;

    public bool $newWindow = false;

    /** For things from beneath an item: that item, which they can go back beneath. */
    public ?string $home = null;

    public bool $hidden = false;

    /** A heading within a subnav, carrying the items under it. */
    public bool $heading;

    /** @var list<NavNode> */
    public array $children = [];

    /** A copy of the item it stands for, which the nav is rendered from. */
    #[Hidden]
    public NavItem $item;

    /** Where the customization puts it. */
    #[Hidden]
    public string $parent;

    #[Hidden]
    public string $defaultParent;

    /**
     * @param  'section'|'item'|'child'  $kind  A section, an item at the root or in a section, or something beneath an item
     */
    public function __construct(
        public string $id,
        public string $kind,
    ) {}

    /**
     * @param  'section'|'item'|'child'  $kind
     */
    public static function fromItem(NavItem $item, string $kind, string $parent): self
    {
        $node = new self((string) $item->id, $kind);
        $node->label = (string) $item->label;
        $node->defaultLabel = $node->label;
        $node->icon = $item->icon;
        $node->defaultIcon = $item->icon;
        $node->iconSvg = $item->iconSvg;
        $node->href = $item->href;
        $node->home = $kind === 'child' ? $parent : null;
        $node->heading = $kind === 'child' && $item->group;
        // A copy, since the nav is rendered onto it, and the nav it came from
        // may be customized again or compared against.
        $node->item = clone $item;
        $node->parent = $parent;
        $node->defaultParent = $parent;

        if ($kind !== 'section' && is_array($item->subnav)) {
            foreach ($item->subnav as $child) {
                $node->children[] = self::fromItem($child, 'child', $node->id);
            }
        }

        return $node;
    }

    public function isCustom(): bool
    {
        return $this->source === 'custom';
    }

    /** Takes a descendant that isn't a heading out from beneath this node. */
    public function detach(string $id): ?self
    {
        foreach ($this->children as $i => $child) {
            if ($child->id === $id) {
                if ($child->heading) {
                    return null;
                }

                array_splice($this->children, $i, 1);

                return $child;
            }

            $found = $child->detach($id);

            if ($found !== null) {
                return $found;
            }
        }

        return null;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            ...Arr::except(get_object_vars($this), ['item', 'parent', 'defaultParent']),
            'children' => array_map(fn (self $child) => $child->toArray(), $this->children),
        ];
    }

    /** @return array<string, mixed> */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
