<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\gql\types\input\criteria;

use craft\gql\arguments\elements\Asset as AssetArguments;
use craft\gql\GqlEntityRegistry;
use GraphQL\Type\Definition\InputObjectType;

/**
 * Class Asset
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.6.0
 */
class Asset extends InputObjectType
{
    public static function getType()
    {
        $typeName = 'AssetCriteriaInput';

        return GqlEntityRegistry::getEntity($typeName) ?: GqlEntityRegistry::createEntity($typeName, new InputObjectType([
            'name' => $typeName,
            'fields' => function() {
                return AssetArguments::getArguments();
            }
        ]));
    }
}
