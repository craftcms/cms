<?php

declare(strict_types=1);

namespace CraftCms\Cms\Ui;

use Closure;
use CraftCms\Cms\Ui\Concerns\InsertsNodes;
use CraftCms\Cms\Ui\Contracts\Node;
use CraftCms\Cms\Ui\Nodes\Group;
use CraftCms\Cms\Ui\Nodes\Tab;
use Illuminate\Support\Str;
use Illuminate\Support\Traits\Conditionable;

/**
 * @since 6.0.0
 */
class Ui
{
    use Conditionable;
    use InsertsNodes;

    /** @param list<Node> $nodes */
    private function __construct(private array $nodes = []) {}

    /** @param list<Node> $nodes */
    public static function make(array $nodes = []): self
    {
        return new self($nodes);
    }

    public function add(Node ...$nodes): static
    {
        array_push($this->nodes, ...$nodes);

        return $this;
    }

    /** @param (Closure(Tab): mixed)|list<Node> $children */
    public function addTab(string $label, array|Closure $children = [], ?string $uid = null): static
    {
        $tab = Tab::make($uid ?? Str::slug($label), $label, is_array($children) ? $children : []);

        if ($children instanceof Closure) {
            $children($tab);
        }

        return $this->add($tab);
    }

    /** @param (Closure(Group): mixed)|list<Node> $children */
    public function addGroup(string $label, array|Closure $children = [], ?string $uid = null): static
    {
        $group = Group::make($uid ?? Str::slug($label), is_array($children) ? $children : [])
            ->label($label);

        if ($children instanceof Closure) {
            $children($group);
        }

        return $this->add($group);
    }

    /** @return list<Node> */
    public function nodes(): array
    {
        return $this->nodes;
    }

    /** @return list<Node> */
    protected function nodeList(): array
    {
        return $this->nodes;
    }

    /** @param list<Node> $nodes */
    protected function setNodeList(array $nodes): void
    {
        $this->nodes = $nodes;
    }
}
