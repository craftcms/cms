<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Contracts;

use CraftCms\Cms\Element\NestedElementManager;

/**
 * NestedIndexConfigProviderInterface is implemented by fields and owner elements whose nested
 * elements' embedded index needs more than {@see NestedElementManager::defaultIndexConfig()},
 * e.g. their view modes, page size, default table columns, or field layouts.
 *
 * Embedded index requests (loading, inline saves, and element actions) resolve their config
 * through it, so they match the index the owner's editor first rendered. A field is asked
 * for its own nested elements; an owner element is asked for the ones it manages by attribute.
 */
interface NestedIndexConfigProviderInterface
{
    /**
     * Returns the embedded index config for an owner's nested elements.
     *
     * @param  string  $attribute  The owner attribute (or `field:<handle>`) the nested elements belong to
     * @param  bool  $static  Whether the index is read-only
     * @return array<string, mixed>
     */
    public function nestedIndexConfig(ElementInterface $owner, string $attribute, bool $static): array;
}
