<?php

declare(strict_types=1);

namespace CraftCms\Cms\Mcp\Elements;

use CraftCms\Cms\Component\TypeRegistry;
use CraftCms\Cms\Element\Elements;
use CraftCms\Cms\Mcp\Elements\Adapters\AddressAdapter;
use CraftCms\Cms\Mcp\Elements\Adapters\AssetAdapter;
use CraftCms\Cms\Mcp\Elements\Adapters\EntryAdapter;
use CraftCms\Cms\Mcp\Elements\Adapters\UserAdapter;
use Illuminate\Container\Attributes\Singleton;

/**
 * Registers the element types the `elements.*` MCP tools support, keyed by their handles.
 *
 * Plugins may register adapters from their service provider:
 *
 * ```php
 * public function boot(ElementAdapterRegistry $adapters): void
 * {
 *     $adapters->register(ProductAdapter::class);
 * }
 * ```
 *
 * @extends TypeRegistry<ElementAdapter>
 *
 * @since 6.0.0
 */
#[Singleton]
class ElementAdapterRegistry extends TypeRegistry
{
    protected const string CONTRACT = ElementAdapter::class;

    protected const array DEFAULT_TYPES = [
        EntryAdapter::class,
        AssetAdapter::class,
        UserAdapter::class,
        AddressAdapter::class,
    ];

    /** @return list<string> */
    public function handles(): array
    {
        return array_values($this->types()
            ->map(static fn (string $type): string => $type::handle())
            ->all());
    }

    /**
     * Finds an adapter by its handle, or by the reference handle or class of the element type it adapts.
     */
    public function find(string $handle): ?ElementAdapter
    {
        $type = $this->typeByIdentity($handle) ?? $this->typeByElementType($handle);

        return $type === null ? null : app($type);
    }

    /** @return class-string<ElementAdapter>|null */
    private function typeByElementType(string $handle): ?string
    {
        $elementType = app(Elements::class)->getElementTypeByRefHandle($handle);

        if ($elementType === null) {
            return null;
        }

        return $this->types()->first(static fn (string $type): bool => $type::elementType() === $elementType);
    }

    /** @param class-string<ElementAdapter> $type */
    #[\Override]
    protected function identity(string $type): string
    {
        return $type::handle();
    }
}
