<?php

declare(strict_types=1);

namespace CraftCms\Cms\Form\Concerns;

use CraftCms\Cms\Form\Contracts\Node;
use CraftCms\Cms\Form\Nodes\Container;
use InvalidArgumentException;

/**
 * Positional insertion for anything holding an ordered list of nodes.
 *
 * `add()` covers the common case of building a form front to back. This covers
 * the other one: a plugin, or a field that renders a form it didn't author,
 * putting something in a particular place in a list it inherited.
 *
 * Implementers own the array; this only reorders it.
 */
trait InsertsNodes
{
    /** @return list<Node> */
    abstract protected function nodeList(): array;

    /** @param list<Node> $nodes */
    abstract protected function setNodeList(array $nodes): void;

    /** Puts nodes at the front, in the order given. */
    public function prepend(Node ...$nodes): static
    {
        return $this->insertAt(0, ...$nodes);
    }

    /**
     * Inserts at a position, counting from the end when negative — the same
     * convention as `array_splice()`. An index past either end clamps rather
     * than throwing, so `insertAt(PHP_INT_MAX, …)` appends.
     */
    public function insertAt(int $index, Node ...$nodes): static
    {
        if ($nodes === []) {
            return $this;
        }

        $list = $this->nodeList();
        array_splice($list, $index, 0, $nodes);
        $this->setNodeList(array_values($list));

        return $this;
    }

    /**
     * Inserts immediately before the identified node.
     *
     * `$target` is a node's UID, or a control-owning node's path in dot
     * notation — whichever gives that node its identity. The search descends
     * into tabs and groups, since that is where fields usually live; the
     * nodes land as siblings of the target, in whatever container holds it.
     *
     * @throws InvalidArgumentException when nothing in the tree matches.
     */
    public function insertBefore(string $target, Node ...$nodes): static
    {
        return $this->insertRelativeTo($target, $nodes, after: false);
    }

    /**
     * Inserts immediately after the identified node.
     *
     * @see insertBefore() for how `$target` is matched.
     *
     * @throws InvalidArgumentException when nothing in the tree matches.
     */
    public function insertAfter(string $target, Node ...$nodes): static
    {
        return $this->insertRelativeTo($target, $nodes, after: true);
    }

    /** @param list<Node> $nodes */
    private function insertRelativeTo(string $target, array $nodes, bool $after): static
    {
        if ($nodes === []) {
            return $this;
        }

        $list = $this->nodeList();

        foreach ($list as $index => $node) {
            if (self::nodeMatches($node, $target)) {
                array_splice($list, $after ? $index + 1 : $index, 0, $nodes);
                $this->setNodeList(array_values($list));

                return $this;
            }
        }

        // Not a sibling — hand off to the container holding it, which mutates
        // in place. Recursing through the public API rather than this private
        // one, since a trait's private members aren't shared between the
        // unrelated classes that use it.
        foreach ($list as $node) {
            if ($node instanceof Container && self::containsNode($node, $target)) {
                $after
                    ? $node->insertAfter($target, ...$nodes)
                    : $node->insertBefore($target, ...$nodes);

                return $this;
            }
        }

        throw new InvalidArgumentException("No form node matches [$target].");
    }

    /** Whether this node, or anything beneath it, answers to the target. */
    private static function containsNode(Node $node, string $target): bool
    {
        return array_any($node->children(), fn ($child) => self::nodeMatches($child, $target) || self::containsNode($child, $target));
    }

    /**
     * A node is identified by its UID, or — when it owns a control — by that
     * control's authored path. Paths may be authored as arrays, so both forms
     * normalize to dot notation before comparison.
     */
    private static function nodeMatches(Node $node, string $target): bool
    {
        if ($node->uid() === $target) {
            return true;
        }

        $path = $node->getControl()?->path();

        if ($path === null) {
            return false;
        }

        return (is_array($path) ? implode('.', $path) : $path) === $target;
    }
}
