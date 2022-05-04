<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\gql\arguments\elements;

use craft\gql\base\ElementArguments;
use craft\gql\types\QueryArgument;
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
                'type' => Type::listOf(QueryArgument::getType()),
                'description' => 'Narrows the query results based on the field the Matrix blocks belong to, per the fields’ IDs.',
            ],
            'primaryOwnerId' => [
                'name' => 'primaryOwnerId',
                'type' => Type::listOf(QueryArgument::getType()),
                'description' => 'Narrows the query results based on the primary owner element of the Matrix blocks, per the owners’ IDs.',
            ],
            'typeId' => Type::listOf(QueryArgument::getType()),
            'type' => [
                'name' => 'type',
                'type' => Type::listOf(Type::string()),
                'description' => 'Narrows the query results based on the Matrix blocks’ block type handles.',
            ],
        ]);
    }

    /**
     * @inheritdoc
     */
    public static function getDraftArguments(): array
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public static function getRevisionArguments(): array
    {
        return [];
    }
}
