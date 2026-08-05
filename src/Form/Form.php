<?php

declare(strict_types=1);

namespace CraftCms\Cms\Form;

use CraftCms\Cms\Form\Contracts\Node;
use Illuminate\Support\Traits\Conditionable;

class Form
{
    use Conditionable;

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

    public function addIf(bool $condition, Node ...$nodes): static
    {
        return $condition ? $this->add(...$nodes) : $this;
    }

    public function addUnless(bool $condition, Node ...$nodes): static
    {
        return $this->addIf(! $condition, ...$nodes);
    }

    /** @return list<Node> */
    public function nodes(): array
    {
        return $this->nodes;
    }
}
