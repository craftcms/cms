<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\gql\arguments\elements;

use Craft;
use craft\elements\Entry as EntryElement;
use craft\gql\base\ElementMutationArguments;
use craft\gql\base\StructureElementArguments;
use craft\gql\types\QueryArgument;
use craft\helpers\Gql as GqlHelper;
use GraphQL\Type\Definition\Type;

/**
 * Class EntryMutation
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.5.0
 */
class EntryMutation extends ElementMutationArguments
{
    /**
     * @inheritdoc
     */
    public static function getArguments(): array
    {
        return array_merge(parent::getArguments(), [
            'authorId' => [
                'name' => 'authorId',
                'type' => Type::nonNull(Type::id()),
                'description' => 'The ID of the user that created this entry.'
            ],
            'postDate' => [
                'name' => 'postDate',
                'type' => Type::listOf(Type::string()),
                'description' => 'When should the entry be posted.'
            ],
            'expiryDate' => [
                'name' => 'expiryDate',
                'type' => Type::listOf(Type::string()),
                'description' => 'When should the entry expire.'
            ],
        ]);
    }
}
