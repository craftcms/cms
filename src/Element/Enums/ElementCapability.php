<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Enums;

use CraftCms\Cms\Element\Contracts\ElementInterface;

/**
 * Optional element type capabilities that element properties and query params can require.
 *
 * @since 6.0.0
 */
enum ElementCapability
{
    case Drafts;
    case Revisions;
    case Structures;

    /**
     * Returns whether an element type has the capability.
     *
     * @param  class-string<ElementInterface>  $elementType
     */
    public function supportedBy(string $elementType): bool
    {
        return match ($this) {
            self::Drafts => $elementType::hasDrafts(),
            self::Revisions => $elementType::supportsRevisions(),
            self::Structures => $elementType::hasStructures(),
        };
    }
}
