<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\gql\arguments\mutations;

use craft\gql\base\ElementMutationArguments;
use GraphQL\Type\Definition\Type;

/**
 * Class Tag
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.5.0
 */
class Tag extends ElementMutationArguments
{
    /**
     * @inheritdoc
     */
    public static function getArguments(): array
    {
        // Tags are weird. No use for a id or a UID.
        return [
            'title' => [
                'name' => 'title',
                'type' => Type::nonNull(Type::string()),
                'description' => 'Title of the tag.'
            ],
        ];
    }
}
