<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\gql\arguments\mutations;

use craft\gql\base\ElementMutationArguments;
use craft\gql\types\DateTime;
use GraphQL\Type\Definition\Type;

/**
 * Class Entry
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.5.0
 */
class Entry extends ElementMutationArguments
{
    /**
     * @inheritdoc
     */
    public static function getArguments(): array
    {
        return array_merge(parent::getArguments(), [
            'authorId' => [
                'name' => 'authorId',
                'type' => Type::id(),
                'description' => 'The ID of the user that created this entry.'
            ],
            'postDate' => [
                'name' => 'postDate',
                'type' => DateTime::getType(),
                'description' => 'When should the entry be posted.'
            ],
            'expiryDate' => [
                'name' => 'expiryDate',
                'type' => DateTime::getType(),
                'description' => 'When should the entry expire.'
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
        ]);
    }
}
