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

    /**
     * Returns the IDs of the sites the index can list, or `null` if it can list any site.
     *
     * The index lists one site at a time: the requested site when it's one of these, or else the first of them.
     * With a single site, the index has no site menu.
     *
     * @return non-empty-list<int>|null
     */
    public function siteIds(): ?array;

    /**
     * Returns whether the index offers a status menu, rather than its scope fixing the elements' status.
     */
    public function showsStatusMenu(): bool;
}
