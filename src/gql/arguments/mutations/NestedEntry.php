<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\gql\arguments\mutations;

use GraphQL\Type\Definition\Type;

/**
 * Class NestedEntry
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.0.0
 */
class NestedEntry extends Entry
{
    /**
     * @inheritdoc
     */
    public static function getArguments(): array
    {
        return array_merge(parent::getArguments(), [
            'ownerId' => [
                'name' => 'ownerId',
                'type' => Type::id(),
                'description' => 'The entry’s owner ID.',
            ],
            'sortOrder' => [
                'name' => 'sortOrder',
                'type' => Type::int(),
                'description' => 'The entry’s sort order.',
            ],
        ]);
    }
}
