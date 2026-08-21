<?php

declare(strict_types=1);

namespace CraftCms\Cms\Form\Nodes;

use CraftCms\Cms\Form\Contracts\Control;
use CraftCms\Cms\Form\Contracts\Node;
use Illuminate\Support\Traits\Conditionable;

abstract class Container implements Node
{
    use Conditionable;

    /** @param list<Node> $children */
    protected function __construct(
        private readonly string $uid,
        private array $children = [],
    ) {}

    final public function add(Node ...$children): static
    {
        array_push($this->children, ...$children);

        return $this;
    }

    final public function uid(): string
    {
        return $this->uid;
    }

    final public function getControl(): ?Control
    {
        return null;
    }

    /** @return list<Node> */
    final public function children(): array
    {
        return $this->children;
    }
}
