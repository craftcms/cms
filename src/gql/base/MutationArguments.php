<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\gql\base;

use Craft;
use craft\gql\types\QueryArgument;
use GraphQL\Type\Definition\Type;

/**
 * Class Arguments
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.5.0
 */
abstract class MutationArguments
{
    /**
     * Returns the argument fields to use in GraphQL mutation definitions.
     *
     * @return array $fields
     */
    public static function getArguments(): array
    {
        return [
            'id' => [
                'name' => 'id',
                'type' => Type::id(),
                'description' => 'Narrows the query results based on the elements’ IDs.'
            ],
            'uid' => [
                'name' => 'uid',
                'type' => Type::string(),
                'description' => 'Narrows the query results based on the elements’ UIDs.'
            ],
        ];
    }

    /**
     * Returns arguments defined by the content fields based on the context.
     *
     * @param mixed $context The element's context, such as a Volume, Entry Type or Matrix Block Type.
     * @return array
     */
    public static function getContentArguments($context): array
    {
        return [];
    }
}
