<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\gql\types\input\criteria;

use craft\gql\arguments\elements\Tag as TagArguments;
use craft\gql\GqlEntityRegistry;
use GraphQL\Type\Definition\InputObjectType;

/**
 * Class Tag
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.6.0
 */
class Tag extends InputObjectType
{
    public static function getType()
    {
        $typeName = 'TagCriteriaInput';

        return GqlEntityRegistry::getEntity($typeName) ?: GqlEntityRegistry::createEntity($typeName, new InputObjectType([
            'name' => $typeName,
            'fields' => function() {
                return TagArguments::getArguments();
            }
        ]));
    }
}
