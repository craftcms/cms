<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Contracts;

use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\ElementIndexSourceSettings;
use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;

/**
 * Decides which elements an embedded element index lists, and how it behaves.
 *
 * @since 6.0.0
 */
interface EmbeddedIndexScope
{
    /**
     * @param  class-string<ElementInterface>  $elementType
     * @param  array<string, mixed>|null  $config
     */
    public function indexSource(string $elementType, ?array $config = null): ElementIndexSourceSettings;

    public function canReorder(): bool;

    /**
     * Limits an index query to the elements the scope allows, in a way that later query params can't override.
     */
    public function restrictQuery(ElementQueryInterface $query): void;
}
