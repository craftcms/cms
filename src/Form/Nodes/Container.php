<?php

declare(strict_types=1);

namespace CraftCms\Cms\Form\Nodes;

use CraftCms\Cms\Form\Contracts\Control;
use CraftCms\Cms\Form\Contracts\Node;

abstract class Container implements Node
{
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

    final public function addIf(bool $condition, Node ...$children): static
    {
        return $condition ? $this->add(...$children) : $this;
    }

    final public function addUnless(bool $condition, Node ...$children): static
    {
        return $this->addIf(! $condition, ...$children);
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
