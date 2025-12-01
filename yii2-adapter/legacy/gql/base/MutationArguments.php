<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\gql\base;

use GraphQL\Type\Definition\Type;

/**
 * Class MutationArguments
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
                'description' => 'Set the element’s ID.',
            ],
            'uid' => [
                'name' => 'uid',
                'type' => Type::string(),
                'description' => 'Set the element’s UID.',
            ],
        ];
    }
}
