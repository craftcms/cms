<?php

declare(strict_types=1);

namespace craft\gql\base;

use CraftCms\Cms\Element\Enums\ElementCapability;

/**
 * @deprecated 6.0.0 override `elementType()` in an element arguments class instead, and `hasStructures()` in its
 * element type.
 */
abstract class StructureElementArguments extends ElementArguments
{
    public static function getArguments(): array
    {
        return array_merge(parent::getArguments(), static::baseArgumentsRequiring(ElementCapability::Structures));
    }
}
