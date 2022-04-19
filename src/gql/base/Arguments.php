<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\gql\base;

use craft\gql\types\QueryArgument;
use GraphQL\Type\Definition\Type;

/**
 * Class Arguments
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.3.0
 */
abstract class Arguments
{
    /**
     * Returns the argument fields to use in GraphQL type definitions.
     *
     * @return array $fields
     */
    public static function getArguments(): array
    {
        return [
            'id' => [
                'name' => 'id',
                'type' => Type::listOf(QueryArgument::getType()),
                'description' => 'Narrows the query results based on the elements’ IDs.',
            ],
            'uid' => [
                'name' => 'uid',
                'type' => Type::listOf(Type::string()),
                'description' => 'Narrows the query results based on the elements’ UIDs.',
            ],
        ];
    }

    /**
     * Returns arguments defined by the content fields.
     *
     * @return array
     */
    public static function getContentArguments(): array
    {
        return [];
    }
}
