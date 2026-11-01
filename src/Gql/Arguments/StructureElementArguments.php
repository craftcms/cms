<?php

declare(strict_types=1);

namespace CraftCms\Cms\Gql\Arguments;

use CraftCms\Cms\Element\Element;

/**
 * @since 6.0.0
 */
abstract class StructureElementArguments extends ElementArguments
{
    #[\Override]
    public static function getArguments(): array
    {
        return array_merge(parent::getArguments(), static::queryParamArguments(Element::class, [
            'withStructure',
            'structureId',
            'level',
            'hasDescendants',
            'ancestorOf',
            'ancestorDist',
            'descendantOf',
            'descendantDist',
            'leaves',
            'nextSiblingOf',
            'prevSiblingOf',
            'positionedAfter',
            'positionedBefore',
        ]));
    }
}
