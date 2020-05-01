<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\gql\base;

use GraphQL\Type\Definition\Type;

/**
 * Class StructureElementArguments
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.3.0
 */
abstract class StructureElementArguments extends ElementArguments
{
    /**
     * @inheritdoc
     */
    public static function getArguments(): array
    {
        return array_merge(parent::getArguments(), [
            'withStructure' => [
                'name' => 'withStructure',
                'type' => Type::boolean(),
                'description' => 'Explicitly determines whether the query should join in the structure data.'
            ],
            'structureId' => [
                'name' => 'structureId',
                'type' => Type::int(),
                'description' => 'Determines which structure data should be joined into the query.'
            ],
            'level' => [
                'name' => 'level',
                'type' => Type::int(),
                'description' => 'Narrows the query results based on the elements’ level within the structure.'
            ],
            'hasDescendants' => [
                'name' => 'hasDescendants',
                'type' => Type::boolean(),
                'description' => 'Narrows the query results based on whether the elements have any descendants.'
            ],
            'ancestorOf' => [
                'name' => 'ancestorOf',
                'type' => Type::int(),
                'description' => 'Narrows the query results to only elements that are ancestors of another element.'
            ],
            'ancestorDist' => [
                'name' => 'ancestorDist',
                'type' => Type::int(),
                'description' => 'Narrows the query results to only elements that are up to a certain distance away from the element specified by `ancestorOf`.'
            ],
            'descendantOf' => [
                'name' => 'descendantOf',
                'type' => Type::int(),
                'description' => 'Narrows the query results to only elements that are descendants of another element.'
            ],
            'descendantDist' => [
                'name' => 'descendantDist',
                'type' => Type::int(),
                'description' => 'Narrows the query results to only elements that are up to a certain distance away from the element specified by `descendantOf`.'
            ],
            'leaves' => [
                'name' => 'leaves',
                'type' => Type::boolean(),
                'description' => 'Narrows the query results based on whether the elements are “leaves” (element with no descendants).'
            ],
            'nextSiblingOf' => [
                'name' => 'nextSiblingOf',
                'type' => Type::int(),
                'description' => 'Narrows the query results to only the entry that comes immediately after another element.'
            ],
            'prevSiblingOf' => [
                'name' => 'prevSiblingOf',
                'type' => Type::int(),
                'description' => 'Narrows the query results to only the entry that comes immediately before another element.'
            ],
            'positionedAfter' => [
                'name' => 'positionedAfter',
                'type' => Type::int(),
                'description' => 'Narrows the query results to only entries that are positioned after another element.'
            ],
            'positionedBefore' => [
                'name' => 'positionedBefore',
                'type' => Type::int(),
                'description' => 'Narrows the query results to only entries that are positioned before another element.'
            ],
        ]);
    }
}
