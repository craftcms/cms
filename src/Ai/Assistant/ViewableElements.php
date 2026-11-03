<?php

declare(strict_types=1);

namespace CraftCms\Cms\Ai\Assistant;

use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Contracts\NestedElementInterface;
use CraftCms\Cms\Mcp\Elements\ElementAdapterRegistry;
use CraftCms\Cms\Mcp\McpActor;

/**
 * Finds the elements the AI assistant’s display tools show, as the model identifies them, among those the user can
 * view.
 *
 * @internal
 *
 * @since 6.0.0
 */
readonly class ViewableElements
{
    public function __construct(
        private ElementAdapterRegistry $adapters,
        private McpActor $actor,
    ) {}

    /**
     * Finds an element the user can view, by its type, ID, and site.
     *
     * @param  array<mixed>  $item
     * @return array{string, ElementInterface}|null The type’s handle, and the element.
     */
    public function find(array $item): ?array
    {
        $type = is_string($item['type'] ?? null) ? $item['type'] : null;
        $adapter = $type !== null ? $this->adapters->find($type) : null;
        $siteId = is_int($item['siteId'] ?? null) ? $item['siteId'] : null;
        $element = $adapter?->find((int) ($item['id'] ?? 0), null, $siteId);

        if (! $adapter || ! $element || ! $adapter->canView($this->actor->user(), $element)) {
            return null;
        }

        return [$adapter::handle(), $element];
    }

    /**
     * Finds the top-level element a nested element, such as a Matrix block, belongs to, through its primary owners.
     * Returns the element itself when it isn’t nested, or its owner’s type isn’t one the MCP tools support, and `null`
     * when the user can’t view its owner.
     *
     * @return array{string, ElementInterface}|null The type’s handle, and the element.
     */
    public function topLevel(string $type, ElementInterface $element): ?array
    {
        $owner = $element;

        while ($owner instanceof NestedElementInterface && ($next = $owner->getPrimaryOwner()) !== null) {
            $owner = $next;
        }

        $adapter = $owner !== $element ? $this->adapters->find($owner::refHandle()) : null;

        if ($adapter === null) {
            return [$type, $element];
        }

        return $adapter->canView($this->actor->user(), $owner) ? [$adapter::handle(), $owner] : null;
    }
}
