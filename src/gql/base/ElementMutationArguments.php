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
 * Class ElementMutationArguments
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.5.0
 */
abstract class ElementMutationArguments extends MutationArguments
{
    /**
     * @inheritdoc
     */
    public static function getArguments(): array
    {
        return array_merge(parent::getArguments(), [
            'title' => [
                'name' => 'title',
                'type' => Type::string(),
                'description' => 'Narrows the query results based on the elements’ titles.'
            ],
            'slug' => [
                'name' => 'slug',
                'type' => Type::string(),
                'description' => 'Narrows the query results based on the elements’ slugs.'
            ],
            'siteId' => [
                'name' => 'siteId',
                'type' => Type::int(),
                'description' => 'Determines which site(s) the elements should be saved to. Defaults to the primary site.'
            ],
            'enabled' => [
                'name' => 'enabled',
                'type' => Type::boolean(),
                'description' => 'Whether the element should be enabled.'
            ],
        ]);
    }
}
