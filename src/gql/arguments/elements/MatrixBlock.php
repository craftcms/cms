<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\gql\arguments\elements;

use craft\gql\base\ElementArguments;
use GraphQL\Type\Definition\Type;

/**
 * Class MatrixBlock
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.3.0
 */
class MatrixBlock extends ElementArguments
{
    /**
     * @inheritdoc
     */
    public static function getArguments(): array
    {
        return array_merge(parent::getArguments(), [
            'fieldId' => [
                'name' => 'fieldId',
                'type' => Type::listOf(Type::int()),
                'description' => 'Narrows the query results based on the field the Matrix blocks belong to, per the fields’ IDs.'
            ],
            'ownerId' => [
                'name' => 'ownerId',
                'type' => Type::listOf(Type::string()),
                'description' => ' Narrows the query results based on the owner element of the Matrix blocks, per the owners’ IDs.'
            ],
            'typeId' => Type::listOf(Type::int()),
            'type' => [
                'name' => 'type',
                'type' => Type::listOf(Type::string()),
                'description' => 'Narrows the query results based on the Matrix blocks’ block type handles.'
            ],
        ]);
    }
}
